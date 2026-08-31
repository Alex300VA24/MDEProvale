# -*- coding: utf-8 -*-

"""
Migración de responsables (SQL Server PROVALE) -> dbsysprovale (MySQL 8).

Lee de la tabla PECOSA las columnas JEF_id (subgerenta de programas sociales =
responsable tipo 'chief') y ALM_id (encargada de PROVALE = responsable tipo
'storekeeper'). Esos ids apuntan a la tabla PERSONA. Obtiene los datos de esas
personas y genera un Seeder Laravel (PeopleSeeder-style) que:

    1. Inserta en `people` las personas que no existan (idempotente por DNI).
    2. Inserta en `responsibles` una fila por (persona, rol), con active=1 solo
       para el responsable mas reciente de cada rol.

Reporta los periodos (min/max PEC_fecha, meses, n. de pecosas) por responsable.

Uso (PowerShell):
    ..\\migracion_productos\\env\\Scripts\\python.exe -X utf8 migrar_responsables.py
    ..\\migracion_productos\\env\\Scripts\\python.exe -X utf8 migrar_responsables.py --seeders  # si el path de los seeders no es el relativo (debug)
"""

import argparse
import csv
import os
from collections import defaultdict

import pyodbc

SRC_DB = "PROVALE"
SERVER = "127.0.0.1,1433"
UID = "alex"
PWD = "admin123"
DRIVER = "{ODBC Driver 17 for SQL Server}"

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
SEEDERS_PATH = os.path.join(BASE_DIR, "..", "database", "seeders")
PERIODOS_FILE = os.path.join(BASE_DIR, "periodos_responsables.csv")

# Valores por defecto para campos que el SQL Server no trae (people.* NOT NULL)
DEFAULT_ADDRESS = "PROVALE"
DEFAULT_PLACE_SECTOR_ID = 1

SEXO_MAP = {1: "M", 2: "F"}

MONTHS = [
    "ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO",
    "JULIO", "AGOSTO", "SETIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE",
]


def connect_src():
    conn = pyodbc.connect(
        f"DRIVER={DRIVER};SERVER={SERVER};UID={UID};PWD={PWD};DATABASE={SRC_DB}"
    )
    return conn


def load_pecosa_responsables(cur):
    """Agrupa JEF_id/ALM_id por periodo: (persona_id, rol) -> lista de PEC_fecha."""
    cur.execute(
        "SELECT JEF_id, ALM_id, PEC_fecha FROM PECOSA "
        "WHERE JEF_id IS NOT NULL OR ALM_id IS NOT NULL "
    )
    periods = defaultdict(list)
    for jef, alm, fecha in cur.fetchall():
        if jef is not None:
            periods[(jef, "chief")].append(fecha.date() if fecha else None)
        if alm is not None:
            periods[(alm, "storekeeper")].append(fecha.date() if fecha else None)
    return periods


def load_personas(cur, persona_ids):
    placeholders = ",".join("?" for _ in persona_ids)
    cur.execute(
        "SELECT PER_id, PER_apellido_pat, PER_apellido_mat, PER_nombre, "
        "PER_documento_num, PER_fecha_nac, PER_sexo, PER_fono_fijo, PER_fono_cell "
        f"FROM PERSONA WHERE PER_id IN ({placeholders})",
        tuple(sorted(persona_ids)),
    )
    return {r[0]: r for r in cur.fetchall()}


def birthdate_score(d):
    if d is None:
        return -1
    if d.month == 1 and d.day == 1:
        return 0
    return 1


def pick_persona(rpes, personas, usage):
    """Entre varios PER_id con el mismo DNI, elige el registro mas completo."""
    best = None
    best_key = (-1, -1)
    for persona_id in rpes:
        rec = personas[persona_id]
        n_pecosas = usage.get(persona_id, 0)
        key = (birthdate_score(rec[5]), n_pecosas)
        if key > best_key:
            best_key = key
            best = rec
    return best


def build_people(periods, personas):
    """people: dict dni -> persona elegida (sin duplicar por DNI)."""
    usage = defaultdict(int)
    for (persona_id, _rol), fechas in periods.items():
        usage[persona_id] += len(fechas)

    dni_to_rpes = defaultdict(list)
    for (persona_id, _rol) in periods:
        dni_to_rpes[personas[persona_id][4]].append(persona_id)

    people = {}
    for dni, rpes in dni_to_rpes.items():
        rec = pick_persona(rpes, personas, usage)
        people[dni] = rec
    return people


def compute_active(periods):
    """active=1 solo para la persona con el PEC_fecha mas reciente de cada rol."""
    latest = {}
    for (persona_id, rol), fechas in periods.items():
        fechas_validas = [f for f in fechas if f is not None]
        if not fechas_validas:
            continue
        max_fecha = max(fechas_validas)
        cur = latest.get(rol)
        if cur is None or max_fecha > cur[1]:
            latest[rol] = (persona_id, max_fecha)
    return latest


def format_fechas(fechas):
    fechas_validas = sorted(f for f in fechas if f is not None)
    if not fechas_validas:
        return None, None
    return fechas_validas[0], fechas_validas[-1]


def months_text(fechas):
    fechas_validas = [f for f in fechas if f is not None]
    if not fechas_validas:
        return ""
    meses = sorted({(f.year, f.month) for f in fechas_validas})
    partes = []
    for year, month in meses:
        partes.append(f"{MONTHS[month - 1]} {year}")
    return ", ".join(partes)


def php_str(value, indent="        "):
    if value is None:
        return "null"
    if isinstance(value, str):
        return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"
    return str(value)


def gender_char(sexo):
    return SEXO_MAP.get(sexo) or "F"


def build_responsibles(periods, personas, active_by_role):
    active_dni = {}
    for rol, (persona_id, _max_fecha) in active_by_role.items():
        active_dni[rol] = personas[persona_id][4]

    grouped = {}
    for (persona_id, rol), fechas in periods.items():
        rec = personas[persona_id]
        dni = rec[4]
        g = grouped.setdefault(dni, {
            "dni": dni,
            "names": rec[3],
            "father_lastname": rec[1],
            "mother_lastname": rec[2],
            "types": {},
        })
        g["types"].setdefault(rol, []).extend(fechas)

    rows = []
    for dni in sorted(grouped):
        g = grouped[dni]
        for rol in ("chief", "storekeeper"):
            fechas = g["types"].get(rol)
            if not fechas:
                continue
            min_f, max_f = format_fechas(fechas)
            rows.append({
                "dni": dni,
                "names": g["names"],
                "father_lastname": g["father_lastname"],
                "mother_lastname": g["mother_lastname"],
                "type": rol,
                "active": 1 if dni == active_dni.get(rol) else 0,
                "min": min_f,
                "max": max_f,
                "n": len(fechas),
                "meses": months_text(fechas),
            })
    return rows


def render_seeder(people, responsibilities, periods, personas):
    now = "now()"
    lines = []
    lines.append("<?php")
    lines.append("")
    lines.append("namespace Database\\Seeders;")
    lines.append("")
    lines.append("use Illuminate\\Database\\Seeder;")
    lines.append("use Illuminate\\Support\\Facades\\DB;")
    lines.append("")
    lines.append("/**")
    lines.append(" * ResponsibleSeeder (generado por migracion_responsables/migrar_responsables.py)")
    lines.append(" * ----------------------------------------------------------------------------")
    lines.append(" * Subgerenta de programas sociales = type 'chief' (PECOSA.JEF_id).")
    lines.append(" * Encargada de PROVALE            = type 'storekeeper' (PECOSA.ALM_id).")
    lines.append(" *")
    lines.append(" * people: inserta las personas que no existan por DNI (address por defecto: 'PROVALE',")
    lines.append(" * place_sector_id por defecto: 1, porque el SQL Server no trae domicilio).")
    lines.append(" *")
    lines.append(" * responsibles: una fila por (persona, rol); active=1 solo para el responsable con")
    lines.append(" * periodo mas reciente de cada rol.")
    lines.append(" *")
    lines.append(" * PERIODOS (min PEC_fecha -> max PEC_fecha, n = pecosas con ese responsable):")
    for p in responsibilities:
        rango = f"{p['min']} -> {p['max']}" if p["min"] else "(sin fecha)"
        lines.append(
            f" *   [{p['type']:>10}] {p['dni']}  {p['father_lastname']} {p['mother_lastname']}, "
            f"{p['names']}  |  {rango}  | n={p['n']}  | meses: {p['meses']}"
        )
    lines.append(" */")
    lines.append("class ResponsibleSeeder extends Seeder")
    lines.append("{")
    lines.append("    public function run(): void")
    lines.append("    {")
    lines.append("        $personaPorDni = [];")
    lines.append("")
    lines.append("        // --- people (solo si el DNI no existe) ---")
    for dni, rec in people.items():
        names, father, mother = rec[3], rec[1], rec[2]
        gender = gender_char(rec[6])
        birthdate = rec[5].strftime("%Y-%m-%d") if rec[5] else None
        tel = (rec[7] or "")[:6] or None
        phone = (rec[8] or "")[:9] or None
        lines.append("        DB::table('people')->updateOrInsert(")
        lines.append("            ['dni' => " + php_str(dni) + "],")
        lines.append("            [")
        lines.append("                'names'            => " + php_str(names) + ",")
        lines.append("                'father_lastname'  => " + php_str(father) + ",")
        lines.append("                'mother_lastname'  => " + php_str(mother) + ",")
        lines.append("                'dni'              => " + php_str(dni) + ",")
        lines.append("                'gender'           => " + php_str(gender) + ",")
        lines.append("                'telephone_number' => " + php_str(tel) + ",")
        lines.append("                'phone_number'     => " + php_str(phone) + ",")
        lines.append("                'birthdate'        => " + php_str(birthdate) + ",")
        lines.append("                'address'          => " + php_str(DEFAULT_ADDRESS) + ",")
        lines.append("                'place_sector_id'  => " + str(DEFAULT_PLACE_SECTOR_ID) + ",")
        lines.append("                'created_at'       => now(),")
        lines.append("                'updated_at'       => now(),")
        lines.append("            ]")
        lines.append("        );")
        lines.append("        $personaPorDni[" + php_str(dni) + "] = DB::table('people')->where('dni', "
                     + php_str(dni) + ")->value('id');")
        lines.append("")
    lines.append("        // --- responsibles (una por persona y rol, idempotente) ---")
    for p in responsibilities:
        lines.append("        if (! DB::table('responsibles')->where('person_id', $personaPorDni["
                     + php_str(p["dni"]) + "])->where('type', " + php_str(p["type"]) + ")->exists()) {")
        lines.append("            DB::table('responsibles')->insert([")
        lines.append("                'person_id' => $personaPorDni[" + php_str(p["dni"]) + "],")
        lines.append("                'type'      => " + php_str(p["type"]) + ",")
        lines.append("                'active'    => " + str(p["active"]) + ",")
        lines.append("                'created_at' => now(),")
        lines.append("                'updated_at' => now(),")
        lines.append("            ]);")
        lines.append("        }")
    lines.append("    }")
    lines.append("}")
    lines.append("")
    return "\n".join(lines)


def write_report(filename, responsibilities):
    with open(filename, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(
            f,
            fieldnames=["rol", "dni", "nombres", "apellido_pat", "apellido_mat", "min_periodo", "max_periodo", "n_pecosas", "meses"],
        )
        writer.writeheader()
        for p in responsibilities:
            writer.writerow({
                "rol": p["type"],
                "dni": p["dni"],
                "nombres": p["names"],
                "apellido_pat": p["father_lastname"],
                "apellido_mat": p["mother_lastname"],
                "min_periodo": p["min"],
                "max_periodo": p["max"],
                "n_pecosas": p["n"],
                "meses": p["meses"],
            })
    print(f"Reporte periodos: {filename}")


def print_console(responsibilities):
    print("\n=== RESPONSABLES ENCONTRADOS ===")
    for p in responsibilities:
        rango = f"{p['min']} -> {p['max']}" if p["min"] else "(sin fecha)"
        activo = "ACTIVO" if p["active"] else "inactivo"
        print(f"  [{p['type']:>10}] {p['dni']}  {p['father_lastname']} {p['mother_lastname']}, "
              f"{p['names']}  |  {rango}  | n_pecosas={p['n']}  | {activo}")
        print(f"       meses: {p['meses']}")


def main():
    parser = argparse.ArgumentParser(description="Genera seeder de responsables desde SQL Server.")
    parser.add_argument("--seeders", default=SEEDERS_PATH, help="Ruta a database/seeders")
    args = parser.parse_args()

    src = connect_src()
    try:
        cur = src.cursor()
        periods = load_pecosa_responsables(cur)
        persona_ids = set()
        for (persona_id, _rol) in periods:
            persona_ids.add(persona_id)
        personas = load_personas(cur, persona_ids)
    finally:
        src.close()

    people = build_people(periods, personas)
    active_by_role = compute_active(periods)
    responsibilities = build_responsibles(periods, personas, active_by_role)

    print_console(responsibilities)
    write_report(PERIODOS_FILE, responsibilities)

    os.makedirs(args.seeders, exist_ok=True)
    seeder_path = os.path.join(args.seeders, "ResponsibleSeeder.php")
    with open(seeder_path, "w", encoding="utf-8") as f:
        f.write(render_seeder(people, responsibilities, periods, personas))
    print(f"Seeder generado: {seeder_path}")
    print(f"\nPersonas unicas: {len(people)} | Responsibles: {len(responsibilities)}")


if __name__ == "__main__":
    main()