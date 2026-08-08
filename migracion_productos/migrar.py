# -*- coding: utf-8 -*-
"""
Migración de datos de PROVALE -> DBSYSPROVALE
Tablas migradas: kardex -> transactions, detalle_pecosa -> detail_pecosas,
pecosa -> pecosas, producto -> products + detail_products.

Uso (PowerShell):
    env\\Scripts\\python.exe migrar.py            # ejecutar migración
    env\\Scripts\\python.exe migrar.py --reset    # limpiar datos migrados antes de re-ejecutar
    env\\Scripts\\python.exe migrar.py --dry-run  # solo validar y mostrar plan, sin insertar
"""

import argparse
import csv
import json
import os
import re
import sys
import time
from datetime import date, datetime

import pyodbc

SRC_DB = "PROVALE"
DST_DB = "DBSYSPROVALE"
SERVER = "127.0.0.1,1433"
UID = "alex"
PWD = "admin123"
DRIVER = "{ODBC Driver 17 for SQL Server}"

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MARKER_FILE = os.path.join(BASE_DIR, "created_ids.json")
FRACTIONAL_LOG = os.path.join(BASE_DIR, "migracion_fraccionarios.csv")
REPORT_FILE = os.path.join(BASE_DIR, "reporte_migracion.csv")

MONTHS = {
    "ENERO": 1, "FEBRERO": 2, "MARZO": 3, "ABRIL": 4, "MAYO": 5,
    "JUNIO": 6, "JULIO": 7, "AGOSTO": 8, "SETIEMBRE": 9, "SEPTIEMBRE": 9,
    "OCTUBRE": 10, "NOVIEMBRE": 11, "DICIEMBRE": 12,
}

PRODUCT_KEYWORDS = [
    ("LECHE", "LECHE EVAPORADA ENTERA"),
    ("HOJUELA", "HOJUELA DE QUINUA"),
    ("HIJUELA", "HOJUELA DE QUINUA"),
    ("AZUCAR", "AZUCAR"),
    ("QUINUA", "HOJUELA DE QUINUA"),
    ("AVENA", "HOJUELA DE QUINUA"),
]

STATE_ACTIVO = 1
STATE_INACTIVO = 2


# ---------------------------------------------------------------------------
# Conexión
# ---------------------------------------------------------------------------
def connect(db_name):
    conn = pyodbc.connect(
        f"DRIVER={DRIVER};SERVER={SERVER};UID={UID};PWD={PWD};DATABASE={db_name}"
    )
    conn.autocommit = False
    return conn


# ---------------------------------------------------------------------------
# Utilidades de normalización
# ---------------------------------------------------------------------------
def norm(s):
    """Normaliza texto para comparaciones: mayúsculas, sin acentos, sin símbolos."""
    if s is None:
        return ""
    s = s.upper().strip()
    table = str.maketrans("ÁÉÍÓÚÜÑ", "AEIOUUN")
    s = s.translate(table)
    return re.sub(r"[^A-Z0-9 ]", "", s)


def round_int(value):
    if value is None:
        return 0
    return int(round(float(value)))


def parse_fechas(desc, fb_start, fb_end):
    """Extrae rango de fechas de la descripción del producto (formato español).
    Ejemplos soportados:
        DEL 01 AL 28 DE FEBRERO DEL 2015
        DEL 15 DE ABRIL AL 15 DE MAYO DEL 2018
        DEL 01 DE ENERO DEL 2022 AL 28 DE FEBRERO DEL 2022
        DEL 16 DE DICIEMBRE DEL 2019 AL 15 DE MARZO DEL 2020
        DEL 01 AL 31 DE DICIEMBRE 2018
    Si no es posible, devuelve los valores de respaldo (fechas del kardex).
    """
    if not desc:
        return fb_start, fb_end

    s = re.sub(r"\s+", " ", desc.upper())
    m_pat = re.compile(
        r"(\d{1,2})\s+DE\s+(ENERO|FEBRERO|MARZO|ABRIL|MAYO|JUNIO|JULIO|"
        r"AGOSTO|SETIEMBRE|SEPTIEMBRE|OCTUBRE|NOVIEMBRE|DICIEMBRE)"
        r"\s*(?:DEL\s*)?(\d{4})?"
    )
    ms = list(m_pat.finditer(s))
    if not ms:
        return fb_start, fb_end

    years = [int(y) for y in re.findall(r"\b(19|20)\d{2}\b", s)]
    fallback_year = fb_end.year if fb_end else 2000

    last = ms[-1]
    end_day = int(last.group(1))
    end_month = MONTHS[last.group(2)]
    end_year = int(last.group(3)) if last.group(3) else (years[-1] if years else fallback_year)

    if len(ms) >= 2:
        first = ms[0]
        start_day = int(first.group(1))
        start_month = MONTHS[first.group(2)]
        start_year = int(first.group(3)) if first.group(3) else end_year
    else:
        before = s[:last.start()]
        al_match = re.search(r"\bDEL\s+(\d{1,2})\s+AL\s+(\d{1,2})\b", before)
        del_match = re.findall(r"\bDEL\s+(\d{1,2})\b", before)
        if al_match:
            start_day = int(al_match.group(1))
        elif del_match:
            start_day = int(del_match[-1])
        else:
            start_day = 1
        start_month = end_month
        start_year = end_year

    try:
        sd = date(start_year, start_month, start_day)
        ed = date(end_year, end_month, end_day)
    except ValueError:
        return fb_start, fb_end

    if sd > ed:
        sd, ed = ed, sd
    if not (2000 <= sd.year <= 2100 and 2000 <= ed.year <= 2100):
        return fb_start, fb_end
    return sd, ed


def classify_product(desc, abrev):
    d = norm(desc)
    for kw, title in PRODUCT_KEYWORDS:
        if kw in d:
            return title
    a = norm(abrev)
    if a in ("LEC", "HIJ"):
        return "LECHE EVAPORADA ENTERA"
    if a in ("HOJ", "AZU"):
        return "HOJUELA DE QUINUA" if a == "HOJ" else "AZUCAR"
    return "OTRO"


def normalize_abbrev(abrev):
    a = norm(abrev)
    if a in ("HIJ", "LEC"):
        return "LEC"
    if a == "HOJ":
        return "HOJ"
    if a == "AZU":
        return "AZU"
    return a[:5] if a else "S/D"


# ---------------------------------------------------------------------------
# Referencias destino
# ---------------------------------------------------------------------------
def load_references(dst):
    refs = {"states": {}, "uoms": {}, "type_transactions": {}, "products": {}, "responsibles": {}}
    with dst.cursor() as cur:
        for row in cur.execute("SELECT id, title FROM states"):
            refs["states"][row[1].strip().upper()] = row[0]
        for row in cur.execute("SELECT id, title FROM uoms"):
            refs["uoms"][norm(row[1])] = row[0]
        for row in cur.execute("SELECT id, title FROM type_transactions"):
            refs["type_transactions"][norm(row[1])] = row[0]
        for row in cur.execute("SELECT id, title, abbreviation FROM products"):
            refs["products"][norm(row[1])] = row[0]
        for row in cur.execute("SELECT id, person_id, type FROM responsibles"):
            refs["responsibles"][norm(row[2])] = row[0]
    return refs


def ensure_uom_kilo(dst, created):
    with dst.cursor() as cur:
        cur.execute("SELECT id FROM uoms WHERE title = 'Kilo'")
        row = cur.fetchone()
        if row:
            return row[0]
        cur.execute("INSERT INTO uoms (title, created_at, updated_at) OUTPUT INSERTED.id VALUES ('Kilo', GETDATE(), GETDATE())")
        new_id = cur.fetchone()[0]
        created.setdefault("uoms", []).append(new_id)
        return new_id


# ---------------------------------------------------------------------------
# Paso 1: productos + lotes (detail_products)
# ---------------------------------------------------------------------------
def migrate_products(src, dst, refs, marker, dry_run):
    log = []
    total = {"creados": 0, "lotes": 0, "nuevos_productos": 0}

    with src.cursor() as cur:
        prods = [dict(zip([c[0] for c in cur.description], r)) for r in cur.execute(
            "SELECT PRO_id, PRO_codigo, PRO_desc, PRO_abrev, PRO_unimedida, PRO_estado, PRO_stock, PRO_fecha_reg FROM PRODUCTO ORDER BY PRO_id"
        )]
        cur.execute("SELECT PRO_id, SUM(KAR_cantidad), MIN(KAR_fecha), MAX(KAR_fecha), MIN(KAR_valorunit) "
                    "FROM KARDEX WHERE KAR_tipo = 1 GROUP BY PRO_id")
        agg = {r[0]: {"qty": r[1], "min": r[2], "max": r[3], "uv": r[4]} for r in cur.fetchall()}

    product_title_to_id = dict(refs["products"])
    pro_to_detail = {}
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    rows = []
    for p in prods:
        pid = p["PRO_id"]
        title = classify_product(p["PRO_desc"], p["PRO_abrev"])
        if title not in product_title_to_id:
            if not dry_run:
                with dst.cursor() as cur:
                    cur.execute(
                        "INSERT INTO products (title, abbreviation, state_id, uom_id, created_at, updated_at) "
                        "OUTPUT INSERTED.id VALUES (?, ?, ?, ?, GETDATE(), GETDATE())",
                        (title, normalize_abbrev(p["PRO_abrev"]), STATE_ACTIVO, 1),
                    )
                    new_pid = cur.fetchone()[0]
                dst.commit()
                product_title_to_id[title] = new_pid
                marker.setdefault("products", []).append(new_pid)
                total["nuevos_productos"] += 1
            else:
                print(f"  [DRY] crearía producto nuevo: {title}")
                product_title_to_id[title] = -1

        a = agg.get(pid, {})
        qty_orig = a.get("qty") or 0
        fb_start = a.get("min")
        fb_end = a.get("max")
        start_date, end_date = parse_fechas(p["PRO_desc"], fb_start and fb_start.date(), fb_end and fb_end.date())
        unit_price = a.get("uv") or 0
        product_id = product_title_to_id[title]

        if float(qty_orig) != float(round_int(qty_orig)):
            log.append(("producto", pid, qty_orig, round_int(qty_orig), title))
        rows.append((product_id, unit_price, round_int(qty_orig), start_date, end_date, now, now))
        pro_to_detail[pid] = len(rows)  # índice 1-based tras inserción

    if not dry_run and rows:
        with dst.cursor() as cur:
            cur.fast_executemany = True
            cur.executemany(
                "INSERT INTO detail_products (product_id, unit_price, quantity, start_date, end_date, created_at, updated_at) "
                "VALUES (?, ?, ?, ?, ?, ?, ?)",
                rows,
            )
            cur.execute("SELECT SCOPE_IDENTITY()")
        dst.commit()

    # mapear PRO_id -> id real
    if not dry_run:
        with dst.cursor() as cur:
            ids = [r[0] for r in cur.execute(
                "SELECT id FROM detail_products WHERE created_at = ? ORDER BY id", (now,))]
        if len(ids) != len(rows):
            # por si hay más detalle con la misma marca de tiempo, buscar por fila
            ids = ids[: len(rows)]
        for idx, real_id in enumerate(ids, start=1):
            for pid, mapped in pro_to_detail.items():
                if mapped == idx:
                    pro_to_detail[pid] = real_id

    total["creados"] = len(rows)
    total["lotes"] = len(rows)
    return pro_to_detail, log, total


# ---------------------------------------------------------------------------
# Paso 2: transacciones (kardex)
# ---------------------------------------------------------------------------
def migrate_transactions(src, dst, refs, pro_to_detail, log, total, dry_run):
    with src.cursor() as cur:
        kardex = [dict(zip([c[0] for c in cur.description], r)) for r in cur.execute(
            "SELECT KAR_id, KAR_fecha, KAR_tipo, KAR_numdoc, KAR_cantidad, KAR_valorunit, "
            "KAR_valortotal, KAR_ajuste, PRO_id FROM KARDEX ORDER BY KAR_id"
        )]

    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    rows = []
    for k in kardex:
        pro = k["PRO_id"]
        dp = pro_to_detail.get(pro)
        if dp is None:
            print(f"  [SKIP] kardex {k['KAR_id']} sin lote para PRO_id={pro}")
            continue
        qty_orig = k["KAR_cantidad"] or 0
        qty = round_int(qty_orig)
        if float(qty_orig) != float(qty):
            log.append(("kardex", k["KAR_id"], qty_orig, qty, None))
        ttype = refs["type_transactions"].get("INGRESO", 1) if k["KAR_tipo"] == 1 else refs["type_transactions"].get("SALIDA", 2)
        rows.append((
            qty,
            float(k["KAR_valorunit"] or 0),
            float(k["KAR_valortotal"] or 0),
            (k["KAR_numdoc"] or "").strip() or None,
            k["KAR_ajuste"],
            k["KAR_fecha"].date() if k["KAR_fecha"] else None,
            dp,
            ttype,
            None,
            None,
            now,
            now,
        ))
        total["transactions"] += 1

    if not dry_run and rows:
        with dst.cursor() as cur:
            cur.fast_executemany = True
            cur.executemany(
                "INSERT INTO transactions (quantity, unit_price, total_price, document_number, adjustment, "
                "transaction_date, detail_product_id, type_transaction_id, product_name, uom_title, created_at, updated_at) "
                "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                rows,
            )
        dst.commit()
    return rows


# ---------------------------------------------------------------------------
# Paso 3: asociaciones (comité -> association)
# ---------------------------------------------------------------------------
def migrate_associations(src, dst, marker, dry_run):
    with src.cursor() as cur:
        comites = [dict(zip([c[0] for c in cur.description], r)) for r in cur.execute(
            "SELECT COM_id, COM_desc FROM COMITE")]
    with dst.cursor() as cur:
        assocs = {norm(r[0]): r[1] for r in cur.execute("SELECT name, id FROM associations")}

    com_to_assoc = {}
    created = []
    with dst.cursor() as cur:
        cur.execute("SELECT ISNULL(MAX(TRY_CAST(code AS INT)), 0) FROM associations")
        next_code = cur.fetchone()[0] + 1
        cur.execute("SELECT MIN(id) FROM resolutions")
        def_resolution = cur.fetchone()[0] or 1
        cur.execute("SELECT MIN(id) FROM place_sectors")
        def_place_sector = cur.fetchone()[0] or 1
        cur.execute("SELECT MIN(id) FROM type_premises")
        def_type_premises = cur.fetchone()[0] or 1
    for c in comites:
        cid = c["COM_id"]
        desc = c["COM_desc"]
        if not desc or not desc.strip():
            desc = f"COMITE {cid}"
        n = norm(desc)
        if n in assocs:
            com_to_assoc[cid] = assocs[n]
            continue
        if dry_run:
            com_to_assoc[cid] = None
            continue
        with dst.cursor() as cur:
            cur.execute(
                "INSERT INTO associations (name, code, address, company_name, resolution_id, state_id, "
                "place_sector_id, type_premises_id, created_at, updated_at) "
                "OUTPUT INSERTED.id VALUES (?, ?, '', '', ?, ?, ?, ?, GETDATE(), GETDATE())",
                (desc, f"{next_code:03d}", def_resolution, STATE_ACTIVO, def_place_sector, def_type_premises),
            )
            new_id = cur.fetchone()[0]
        next_code += 1
        assocs[n] = new_id
        created.append(new_id)
        com_to_assoc[cid] = new_id

    if created:
        dst.commit()
        marker.setdefault("associations", []).extend(created)
    return com_to_assoc, created


# ---------------------------------------------------------------------------
# Paso 4: pecosas (una por fila PECOSA, numeración año+mes+código)
# ---------------------------------------------------------------------------
def migrate_pecosas(src, dst, refs, com_to_assoc, marker, dry_run):
    with src.cursor() as cur:
        pecas = [dict(zip([c[0] for c in cur.description], r)) for r in cur.execute(
            "SELECT pec.PEC_id, pec.PEC_codigo, pec.PEC_fecha, pec.PEC_estado, pec.COM_id, pec.PEC_observacion, "
            "pec.PER_id, p.PER_nombre, p.PER_apellido_pat, p.PER_apellido_mat "
            "FROM PECOSA pec LEFT JOIN PERSONA p ON p.PER_id = pec.PER_id ORDER BY pec.PEC_id"
        )]

    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    rows = []
    pec_to_pecosa = {}
    chief_id = refs["responsibles"].get("CHIEF", 1)
    storekeeper_id = refs["responsibles"].get("STOREKEEPER", 2)

    idx = 0
    for r in pecas:
        idx += 1
        codigo = (r["PEC_codigo"] or "").strip()
        fecha = r["PEC_fecha"]
        year = fecha.year if fecha else 0
        month = fecha.month if fecha else 0
        numero = f"{year % 100:02d}{month:02d}{codigo[-4:].zfill(4)}"
        state_id = STATE_ACTIVO if r["PEC_estado"] == 1 else (STATE_INACTIVO if r["PEC_estado"] == 2 else STATE_ACTIVO)
        assoc = com_to_assoc.get(r["COM_id"])
        if assoc is None and not dry_run:
            assoc = 1
        obs_parts = []
        if r["PEC_observacion"]:
            obs_parts.append(r["PEC_observacion"])
        name = " ".join(x for x in (r["PER_nombre"], r["PER_apellido_pat"], r["PER_apellido_mat"]) if x).strip()
        if name:
            obs_parts.append("Beneficiario(s): " + name)
        observation = "\n".join(obs_parts) if obs_parts else None

        rows.append((
            numero, observation, fecha, chief_id, storekeeper_id, None, None,
            state_id, assoc, None, None, None, None, None, None, None, None,
            None, None, None, None, None, None, 1, now, now,
        ))
        pec_to_pecosa[r["PEC_id"]] = idx

    if not dry_run and rows:
        with dst.cursor() as cur:
            cur.fast_executemany = True
            cur.executemany(
                "INSERT INTO pecosas (pecosa_number, observation, delivery_date, chief_id, storekeeper_id, "
                "managing_partner_id, president_id, state_id, association_id, "
                "chief_name, storekeeper_name, managing_partner_name, president_name, "
                "association_name, association_code, chief_dni, storekeeper_dni, managing_partner_dni, "
                "president_dni, association_address, association_zone_code, association_zone_name, "
                "association_sector_name, beneficiaries_count, created_at, updated_at) "
                "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                rows,
            )
        dst.commit()

    if not dry_run:
        with dst.cursor() as cur:
            ids = [r[0] for r in cur.execute("SELECT id FROM pecosas WHERE created_at = ? ORDER BY id", (now,))]
        if len(ids) != len(rows):
            ids = ids[: len(rows)]
        for idx, real_id in enumerate(ids, start=1):
            for pec_id, mapped in pec_to_pecosa.items():
                if mapped == idx:
                    pec_to_pecosa[pec_id] = real_id

    return pec_to_pecosa


# ---------------------------------------------------------------------------
# Paso 5: detalle de pecosas
# ---------------------------------------------------------------------------
def migrate_detail_pecosas(src, dst, pro_to_detail, codigo_to_pecosa, log, total, dry_run):
    with src.cursor() as cur:
        det = [dict(zip([c[0] for c in cur.description], r)) for r in cur.execute(
            "SELECT d.DPE_id, d.PEC_id, d.PRO_id, d.DPE_cantidad, d.DPE_valorunit "
            "FROM DETALLE_PECOSA d ORDER BY d.DPE_id"
        )]

    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    rows = []
    for d in det:
        pecosa_id = codigo_to_pecosa.get(d["PEC_id"])
        dp = pro_to_detail.get(d["PRO_id"])
        if pecosa_id is None or dp is None:
            print(f"  [SKIP] detalle {d['DPE_id']} sin pecosa/lote (PEC={d['PEC_id']} PRO={d['PRO_id']})")
            continue
        qty_orig = d["DPE_cantidad"] or 0
        qty = round_int(qty_orig)
        if float(qty_orig) != float(qty):
            log.append(("detalle_pecosa", d["DPE_id"], qty_orig, qty, None))
        unit_price = float(d["DPE_valorunit"] or 0)
        subtotal = round(qty * unit_price, 2)
        rows.append((
            1, qty, qty, unit_price, subtotal, dp, pecosa_id,
            None, None, None, now, now,
        ))
        total["detail_pecosas"] += 1

    if not dry_run and rows:
        with dst.cursor() as cur:
            cur.fast_executemany = True
            cur.executemany(
                "INSERT INTO detail_pecosas (priority, quantity, delivered_quantity, unit_price, subtotal, "
                "detail_product_id, pecosa_id, product_name, product_abbreviation, uom_title, created_at, updated_at) "
                "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                rows,
            )
        dst.commit()
    return rows


# ---------------------------------------------------------------------------
# Paso 6: reconciliación de stock (product_stocks) desde salidas de kardex
# ---------------------------------------------------------------------------
def migrate_stocks(src, dst, pro_to_detail, codigo_to_pecosa, log, dry_run):
    with src.cursor() as cur:
        salidas = [dict(zip([c[0] for c in cur.description], r)) for r in cur.execute(
            "SELECT KAR_id, KAR_cantidad, PRO_id FROM KARDEX WHERE KAR_tipo = 2 ORDER BY KAR_id"
        )]

    kar_to_pecosa = {}
    with src.cursor() as cur:
        for r in cur.execute("SELECT KAR_id, PEC_id FROM PECOSA WHERE KAR_id IS NOT NULL"):
            kar_to_pecosa[r[0]] = codigo_to_pecosa.get(r[1])

    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    rows = []
    stock_count = 0
    for s in salidas:
        dp = pro_to_detail.get(s["PRO_id"])
        if dp is None:
            continue
        pecosa_id = kar_to_pecosa.get(s["KAR_id"])
        qty_orig = s["KAR_cantidad"] or 0
        qty = round_int(qty_orig)
        obs = "Salida por Pecosa #" + str(pecosa_id) if pecosa_id else "Salida por Kardex"
        if float(qty_orig) != float(qty):
            obs += f" (cant. original: {qty_orig})"
        rows.append((dp, pecosa_id, qty, obs, now, now))
        stock_count += 1

    if not dry_run and rows:
        with dst.cursor() as cur:
            cur.fast_executemany = True
            cur.executemany(
                "INSERT INTO product_stocks (detail_product_id, pecosa_id, quantity, observation, created_at, updated_at) "
                "VALUES (?, ?, ?, ?, ?, ?)",
                rows,
            )
        dst.commit()
    return stock_count


# ---------------------------------------------------------------------------
# Validación / reporte
# ---------------------------------------------------------------------------
def validate(dst, dry_run):
    print("\n=== VALIDACIÓN ===")
    counts = {}
    with dst.cursor() as cur:
        for tbl in ("detail_products", "transactions", "pecosas", "detail_pecosas", "product_stocks"):
            cur.execute(f"SELECT COUNT(*) FROM {tbl}")
            counts[tbl] = cur.fetchone()[0]
        orphans = {}
        checks = {
            "transactions": "SELECT COUNT(*) FROM transactions t WHERE NOT EXISTS (SELECT 1 FROM detail_products d WHERE d.id = t.detail_product_id)",
            "detail_pecosas": "SELECT COUNT(*) FROM detail_pecosas d WHERE NOT EXISTS (SELECT 1 FROM detail_products p WHERE p.id = d.detail_product_id)",
            "detail_pecosas_pecosa": "SELECT COUNT(*) FROM detail_pecosas d WHERE NOT EXISTS (SELECT 1 FROM pecosas p WHERE p.id = d.pecosa_id)",
            "product_stocks": "SELECT COUNT(*) FROM product_stocks s WHERE NOT EXISTS (SELECT 1 FROM detail_products d WHERE d.id = s.detail_product_id)",
            "pecosas_assoc": "SELECT COUNT(*) FROM pecosas p WHERE p.association_id IS NULL OR NOT EXISTS (SELECT 1 FROM associations a WHERE a.id = p.association_id)",
        }
        for name, sql in checks.items():
            cur.execute(sql)
            orphans[name] = cur.fetchone()[0]
    for t, c in counts.items():
        print(f"  {t}: {c}")
    for n, c in orphans.items():
        flag = "OK" if c == 0 else f"!! {c} huérfanos"
        print(f"  {n}: {flag}")
    return counts


def negative_lots(dst):
    """Lotes con stock negativo (cantidad - usado < 0). Suelen ser artefactos
    del redondeo a entero de salidas fraccionarias del kardex fuente."""
    rows = []
    with dst.cursor() as cur:
        cur.execute(
            "SELECT dp.id, p.title, dp.quantity, ISNULL(s.used,0), "
            "dp.quantity - ISNULL(s.used,0) "
            "FROM detail_products dp "
            "JOIN products p ON p.id = dp.product_id "
            "LEFT JOIN (SELECT detail_product_id, SUM(quantity) AS used "
            "FROM product_stocks GROUP BY detail_product_id) s "
            "ON s.detail_product_id = dp.id "
            "WHERE dp.quantity - ISNULL(s.used,0) < 0 ORDER BY dp.id"
        )
        for r in cur.fetchall():
            rows.append([r[0], r[1], r[2], r[3], r[4]])
    return rows


# ---------------------------------------------------------------------------
# Reset
# ---------------------------------------------------------------------------
def reset(dst, marker):
    print("Limpiando datos migrados...")
    with dst.cursor() as cur:
        cur.execute("DELETE FROM product_stocks")
        cur.execute("DELETE FROM detail_pecosas")
        cur.execute("DELETE FROM transactions")
        cur.execute("DELETE FROM pecosas")
        cur.execute("DELETE FROM detail_products")
        for uid_ in marker.get("uoms", []):
            cur.execute("DELETE FROM uoms WHERE id = ?", (uid_,))
        for aid in marker.get("associations", []):
            cur.execute("DELETE FROM associations WHERE id = ?", (aid,))
        for pid in marker.get("products", []):
            cur.execute("DELETE FROM products WHERE id = ?", (pid,))
    dst.commit()
    print("Reset completado.")


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
def main():
    parser = argparse.ArgumentParser(description="Migración PROVALE -> DBSYSPROVALE")
    parser.add_argument("--reset", action="store_true", help="Limpiar datos migrados y volver a ejecutar")
    parser.add_argument("--dry-run", action="store_true", help="Simular sin insertar datos")
    args = parser.parse_args()

    t0 = time.time()
    src = connect(SRC_DB)
    dst = connect(DST_DB)
    print(f"Conectado a {SRC_DB} y {DST_DB}")

    marker = {}
    if os.path.exists(MARKER_FILE):
        with open(MARKER_FILE, "r", encoding="utf-8") as f:
            marker = json.load(f)

    if args.reset:
        reset(dst, marker)
        marker = {}

    log = []
    total = {"transactions": 0, "detail_pecosas": 0}

    refs = load_references(dst)
    if args.dry_run:
        refs["uoms"]["KILO"] = -1
    else:
        kilo_id = ensure_uom_kilo(dst, marker)
        refs["uoms"]["KILO"] = kilo_id
        if marker.get("uoms"):
            dst.commit()

    print("\n[1/6] Productos y lotes (detail_products)...")
    pro_to_detail, prod_log, prod_total = migrate_products(src, dst, refs, marker, args.dry_run)
    log.extend(prod_log)
    print(f"  Lotes creados: {prod_total['lotes']} | productos nuevos: {prod_total['nuevos_productos']}")

    print("[2/6] Transacciones (kardex)...")
    migrate_transactions(src, dst, refs, pro_to_detail, log, total, args.dry_run)
    print(f"  Transacciones: {total['transactions']}")

    print("[3/6] Asociaciones (comités)...")
    com_to_assoc, created_assocs = migrate_associations(src, dst, marker, args.dry_run)
    print(f"  Asociaciones creadas: {len(created_assocs)}")

    print("[4/6] Pecosas (una por fila PECOSA)...")
    pec_to_pecosa = migrate_pecosas(src, dst, refs, com_to_assoc, marker, args.dry_run)
    print(f"  Pecosas: {len(pec_to_pecosa)}")

    print("[5/6] Detalle de pecosas (detail_pecosas)...")
    migrate_detail_pecosas(src, dst, pro_to_detail, pec_to_pecosa, log, total, args.dry_run)
    print(f"  Detalles: {total['detail_pecosas']}")

    print("[6/6] Reconciliación de stock (product_stocks)...")
    stock_count = migrate_stocks(src, dst, pro_to_detail, pec_to_pecosa, log, args.dry_run)
    print(f"  Product stocks: {stock_count}")

    if marker:
        with open(MARKER_FILE, "w", encoding="utf-8") as f:
            json.dump(marker, f, ensure_ascii=False, indent=2)

    # escribir log de fraccionarios
    with open(FRACTIONAL_LOG, "w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["origen", "id_origen", "cantidad_original", "cantidad_redondeada", "producto"])
        for row in log:
            w.writerow(row)
    print(f"\nLog de cantidades fraccionarias: {FRACTIONAL_LOG}")

    if not args.dry_run:
        counts = validate(dst, args.dry_run)
        neg = negative_lots(dst)
        with open(REPORT_FILE, "w", newline="", encoding="utf-8") as f:
            w = csv.writer(f)
            w.writerow(["tabla", "total"])
            for t, c in counts.items():
                w.writerow([t, c])
            if neg:
                w.writerow([])
                w.writerow(["NOTAS: lotes con stock negativo por redondeo de salidas fraccionarias"])
                w.writerow(["detail_product_id", "producto", "cantidad_lote", "usado", "disponible"])
                for row in neg:
                    w.writerow(row)
        print(f"Reporte: {REPORT_FILE}")

    print(f"\nTiempo total: {time.time() - t0:.2f}s")
    if args.dry_run:
        print("DRY RUN: no se insertaron datos.")

    src.close()
    dst.close()


if __name__ == "__main__":
    main()
