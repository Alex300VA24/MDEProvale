# -*- coding: utf-8 -*-

"""
Mapa de responsables por pecosa (SQL Server PROVALE -> dbsysprovale).

Reproduce la numeracion de `exportar_excel.py` (pecosa_number = yy + seq por
ano, orden (year, PEC_fecha, PEC_id)) y genera los artefactos que asignan a
cada pecosa local la subgerenta de programas sociales (PECOSA.JEF_id = type
'chief') y la encargada de PROVALE (PECOSA.ALM_id = type 'storekeeper') que
firmaron esa pecosa:

    - database/seeders/data/pecosas_responsables.json
      [[pecosa_number, jef_dni, alm_dni], ...] (null si el origen no trae dato)
    - database/seeders/PecosaResponsableSeeder.php
      Seeder Laravel que resuelve responsables por DNI y actualiza las pecosas.
    - migracion_responsables/pecosas_responsable.csv (reporte)

Pecosas sin responsable en el origen (JEF_id y ALM_id null) quedan como estan.

Uso (PowerShell):
    ..\\migracion_productos\\env\\Scripts\\python.exe -X utf8 mapear_pecosas.py
"""

import csv
import datetime
import json
import os

import pyodbc

SRC_DB = "PROVALE"
SERVER = "127.0.0.1,1433"
UID = "alex"
PWD = "admin123"
DRIVER = "{ODBC Driver 17 for SQL Server}"

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
SEEDERS_PATH = os.path.join(BASE_DIR, "..", "database", "seeders")
SEEDER_DATA = os.path.join(SEEDERS_PATH, "data")
REPORT_FILE = os.path.join(BASE_DIR, "pecosas_responsable.csv")

DATE_FROM = "2019-01-01"
DATE_TO = "2026-12-31"


def connect_src():
    conn = pyodbc.connect(
        f"DRIVER={DRIVER};SERVER={SERVER};UID={UID};PWD={PWD};DATABASE={SRC_DB}"
    )
    return conn


def load_pecosas(cur):
    """Numera pecosas como exportar_excel.py y devuelve numero -> (JEF_id, ALM_id)."""
    cur.execute(
        "SELECT PEC_id, PEC_fecha, JEF_id, ALM_id FROM PECOSA "
        "WHERE PEC_fecha >= ? AND PEC_fecha <= ? ORDER BY PEC_fecha, PEC_id",
        (DATE_FROM, DATE_TO),
    )
    rows = cur.fetchall()
    rows.sort(key=lambda r: (
        r[1].year if r[1] else 0,
        r[1] or datetime.datetime.min,
        r[0],
    ))
    year_counter = {}
    numbered = {}
    for r in rows:
        fecha = r[1]
        year = fecha.year if fecha else 0
        seq = year_counter.get(year, 0) + 1
        year_counter[year] = seq
        numero = f"{year % 100:02d}{seq:04d}" if fecha else None
        if numero:
            numbered[numero] = (r[2], r[3])
    return numbered


def load_dnis(cur, persona_ids):
    if not persona_ids:
        return {}
    placeholders = ",".join("?" for _ in persona_ids)
    cur.execute(
        "SELECT PER_id, PER_documento_num FROM PERSONA "
        f"WHERE PER_id IN ({placeholders})",
        tuple(sorted(persona_ids)),
    )
    return dict(cur.fetchall())


def php_str(value, indent="        "):
    if value is None:
        return "null"
    return "'" + str(value).replace("\\", "\\\\").replace("'", "\\'") + "'"


def render_seeder(rows):
    lines = []
    lines.append("<?php")
    lines.append("")
    lines.append("namespace Database\\Seeders;")
    lines.append("")
    lines.append("use Illuminate\\Database\\Seeder;")
    lines.append("use Illuminate\\Support\\Facades\\DB;")
    lines.append("")
    lines.append("/**")
    lines.append(" * PecosaResponsableSeeder (generado por migracion_responsables/mapear_pecosas.py)")
    lines.append(" * ----------------------------------------------------------------------------")
    lines.append(" * Actualiza pecosas ya migradas con los responsables que firmaron cada una:")
    lines.append(" *   - chief       = subgerenta de programas sociales (PECOSA.JEF_id).")
    lines.append(" *   - storekeeper = encargada de PROVALE (PECOSA.ALM_id).")
    lines.append(" *")
    lines.append(" * Los responsables se resuelven en runtime por DNI contra responsibles/people.")
    lines.append(" * Las pecosas del origen sin responsable quedan sin tocar (chief_id/")
    lines.append(" * storekeeper_id siguen null).")
    lines.append(f" *")
    lines.append(f" * Pecosas a actualizar: {len(rows)} (de data/pecosas_responsables.json).")
    lines.append(" */")
    lines.append("class PecosaResponsableSeeder extends Seeder")
    lines.append("{")
    lines.append("    public function run(): void")
    lines.append("    {")
    lines.append("        $ruta = __DIR__ . '/data/pecosas_responsables.json';")
    lines.append("")
    lines.append("        if (! is_file($ruta)) {")
    lines.append("            throw new \\RuntimeException(\"No se encontro {$ruta}. Copia la carpeta data/ junto a los seeders.\");")
    lines.append("        }")
    lines.append("")
    lines.append("        $filas = json_decode(file_get_contents($ruta), true);")
    lines.append("        if (! is_array($filas)) {")
    lines.append("            throw new \\RuntimeException(\"El archivo {$ruta} no tiene un JSON valido.\");")
    lines.append("        }")
    lines.append("")
    lines.append("        // Responsables por rol, resueltos por DNI de la persona.")
    lines.append("        $responsable = [];")
    lines.append("        $persona = [];")
    lines.append("        foreach (['chief', 'storekeeper'] as $rol) {")
    lines.append("            $rows = DB::table('responsibles')")
    lines.append("                ->join('people', 'people.id', '=', 'responsibles.person_id')")
    lines.append("                ->where('responsibles.type', $rol)")
    lines.append("                ->select('responsibles.id', 'people.dni')")
    lines.append("                ->get();")
    lines.append("            foreach ($rows as $r) {")
    lines.append("                $responsable[$rol][$r->dni] = $r->id;")
    lines.append("                $persona[$r->dni] = DB::table('people')->where('dni', $r->dni)->first();")
    lines.append("            }")
    lines.append("        }")
    lines.append("")
    lines.append("        $ahora = now();")
    lines.append("        foreach ($filas as $fila) {")
    lines.append("            [$numero, $dniJef, $dniAlm] = $fila;")
    lines.append("")
    lines.append("            $updates = [];")
    lines.append("            foreach ([")
    lines.append("                'chief'       => [$dniJef, 'chief_id', 'chief_dni', 'chief_name'],")
    lines.append("                'storekeeper' => [$dniAlm, 'storekeeper_id', 'storekeeper_dni', 'storekeeper_name'],")
    lines.append("            ] as $rol => [$dni, $idCol, $dniCol, $nameCol]) {")
    lines.append("                if ($dni !== null && isset($responsable[$rol][$dni])) {")
    lines.append("                    $updates[$idCol] = $responsable[$rol][$dni];")
    lines.append("                    $updates[$dniCol] = $dni;")
    lines.append("                    $updates[$nameCol] = trim(implode(' ', array_filter([")
    lines.append("                        $persona[$dni]->names,")
    lines.append("                        $persona[$dni]->father_lastname,")
    lines.append("                        $persona[$dni]->mother_lastname,")
    lines.append("                    ])));")
    lines.append("                }")
    lines.append("            }")
    lines.append("")
    lines.append("            if (! $updates) {")
    lines.append("                continue;")
    lines.append("            }")
    lines.append("            $updates['updated_at'] = $ahora;")
    lines.append("")
    lines.append("            DB::table('pecosas')->where('pecosa_number', $numero)->update($updates);")
    lines.append("        }")
    lines.append("    }")
    lines.append("}")
    lines.append("")
    return "\n".join(lines)


def main():
    src = connect_src()
    try:
        cur = src.cursor()
        numbered = load_pecosas(cur)
        persona_ids = {pid for jef, alm in numbered.values() for pid in (jef, alm) if pid is not None}
        dnis = load_dnis(cur, persona_ids)
    finally:
        src.close()

    rows = []
    sin_numero = 0
    for numero in sorted(numbered):
        jef_id, alm_id = numbered[numero]
        jef_dni = dnis.get(jef_id) if jef_id is not None else None
        alm_dni = dnis.get(alm_id) if alm_id is not None else None
        if jef_dni is None and alm_dni is None:
            continue
        rows.append([numero, jef_dni, alm_dni])

    os.makedirs(SEEDER_DATA, exist_ok=True)
    with open(os.path.join(SEEDER_DATA, "pecosas_responsables.json"), "w", encoding="utf-8") as f:
        json.dump(rows, f, ensure_ascii=True)

    seeder_path = os.path.join(SEEDERS_PATH, "PecosaResponsableSeeder.php")
    with open(seeder_path, "w", encoding="utf-8") as f:
        f.write(render_seeder(rows))

    with open(REPORT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["pecosa_number", "jef_dni", "alm_dni"])
        writer.writerows(rows)

    print(f"Mapa: {len(rows)} pecosas con responsable (de {len(numbered)} numeradas).")
    print(f"JSON : {SEEDER_DATA}\\pecosas_responsables.json")
    print(f"Seeder: {seeder_path}")
    print(f"Reporte: {REPORT_FILE}")


if __name__ == "__main__":
    main()