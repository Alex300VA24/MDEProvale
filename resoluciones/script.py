from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime
import os
import re
from bs4 import BeautifulSoup
from dateutil.relativedelta import relativedelta
import requests

# --- CONFIGURACIÓN ---
SEEDER_PATH = os.path.join("database", "seeders", "ResolutionSeeder.php")
print(SEEDER_PATH)
ENDPOINT = (
    "https://www.muniesperanza.gob.pe/website/loads/cargar_archivos.php"
)
HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
    )
}

CACHE_STORAGE = {}


def extraer_resoluciones_de_seeder(path_archivo: str):
  """Lee el archivo PHP y extrae las dicts de $resolutions mediante regex."""
  if not os.path.exists(path_archivo):
    print(f"❌ Error: No se encontró el archivo en {path_archivo}")
    return []

  with open(path_archivo, "r", encoding="utf-8") as f:
    contenido = f.read()

  # Buscar patrones ['document' => '...', 'date_start' => '...', 'date_end' => '...']
  pattern = r"\['document'\s*=>\s*'([^']+)'\s*,\s*'date_start'\s*=>\s*'([^']+)'\s*,\s*'date_end'\s*=>\s*'([^']+)'\]"
  matches = re.findall(pattern, contenido)

  resoluciones = []
  for doc, start, end in matches:
    resoluciones.append({"document": doc, "date_start": start, "date_end": end})

  return resoluciones


def parsear_numero_documento(documento: str):
  """Descompone '0921-2021' en ('0921', '2021')."""
  partes = documento.strip().split("-")
  if len(partes) == 2:
    return partes[0], partes[1]
  return documento, ""


def consultar_mes(anio: str, mes: int, numero: str, timeout: int = 12):
  """Realiza la petición HTTP al endpoint para un mes específico."""
  mes_str = f"{mes:02d}"
  param_d = f"{anio}|{mes_str}|{numero}|2"

  try:
    res = requests.get(
        ENDPOINT, params={"d": param_d}, headers=HEADERS, timeout=timeout
    )
    if res.status_code == 200 and res.text.strip():
      return mes, res.text
  except requests.RequestException:
    pass
  return mes, None


def parsear_coincidencia(html_content: str, documento: str):
  """Busca filas <tr class="od"> con el número de documento y extrae URL y fecha."""
  soup = BeautifulSoup(html_content, "html.parser")
  filas = soup.find_all("tr", class_="od")

  doc_limpio = documento.replace("-", "")

  for fila in filas:
    texto_fila = fila.get_text()

    if documento in texto_fila or doc_limpio in texto_fila:
      enlace = fila.find("a", href=True)
      pdf_url = enlace["href"] if enlace else None

      # Detectar fecha (formato DD/MM/YYYY o YYYY-MM-DD)
      fecha_real = None
      match_dmy = re.search(r"\b(\d{2})[/-](\d{2})[/-](\d{4})\b", texto_fila)
      match_ymd = re.search(r"\b(\d{4})[/-](\d{2})[/-](\d{2})\b", texto_fila)

      if match_dmy:
        fecha_real = f"{match_dmy.group(3)}-{match_dmy.group(2)}-{match_dmy.group(1)}"
      elif match_ymd:
        fecha_real = f"{match_ymd.group(1)}-{match_ymd.group(2)}-{match_ymd.group(3)}"

      return {"pdf_url": pdf_url, "fecha_inicio_real": fecha_real}

  return None


def buscar_resolucion_web(documento: str, fecha_inicio_esperada: str):
  """Aplica la estrategia del mes preferido y luego 11 meses en paralelo."""
  if documento in CACHE_STORAGE:
    return CACHE_STORAGE[documento]

  numero_doc, anio_doc = parsear_numero_documento(documento)
  dt_esperada = datetime.strptime(fecha_inicio_esperada, "%Y-%m-%d")

  mes_preferido = dt_esperada.month
  anio = anio_doc if anio_doc else str(dt_esperada.year)

  # 1. Probar el mes preferido primero
  _, html_preferido = consultar_mes(anio, mes_preferido, numero_doc)
  if html_preferido:
    match = parsear_coincidencia(html_preferido, documento)
    if match:
      CACHE_STORAGE[documento] = {"existe": True, **match}
      return CACHE_STORAGE[documento]

  # 2. Consultar los 11 meses restantes en paralelo
  meses_restantes = [m for m in range(1, 13) if m != mes_preferido]
  resultado_match = None

  with ThreadPoolExecutor(max_workers=11) as executor:
    futures = {
        executor.submit(consultar_mes, anio, m, numero_doc): m
        for m in meses_restantes
    }

    for future in as_completed(futures):
      _, html_mes = future.result()
      if html_mes:
        match = parsear_coincidencia(html_mes, documento)
        if match:
          resultado_match = match
          executor.shutdown(wait=False, cancel_futures=True)
          break

  if resultado_match:
    CACHE_STORAGE[documento] = {"existe": True, **resultado_match}
  else:
    CACHE_STORAGE[documento] = {"existe": False}

  return CACHE_STORAGE[documento]


def auditar():
  print(f"📖 Leyendo datos desde {SEEDER_PATH}...")
  resoluciones = extraer_resoluciones_de_seeder('..\\' + SEEDER_PATH)

  if not resoluciones:
    print(
        "❌ No se encontraron resoluciones en el seeder o el archivo no"
        " existe."
    )
    return

  print(f"🔍 Se encontraron {len(resoluciones)} resoluciones para auditar.\n")

  reporte = {"no_existen": [], "incongruentes": [], "correctas": []}

  for index, res in enumerate(resoluciones, 1):
    doc = res["document"]
    expected_start = res["date_start"]

    print(
        f"[{index}/{len(resoluciones)}] Evaluando {doc}...", end="", flush=True
    )

    web_data = buscar_resolucion_web(doc, expected_start)

    if not web_data["existe"]:
      print(" ❌ [NO EXISTE]")
      reporte["no_existen"].append(
          {"document": doc, "date_start": expected_start}
      )
    else:
      real_start = web_data.get("fecha_inicio_real")

      if real_start and real_start != expected_start:
        print(" ⚠️ [FECHA INCORRECTA]")

        dt_start_real = datetime.strptime(real_start, "%Y-%m-%d")
        dt_end_real = dt_start_real + relativedelta(years=2)

        reporte["incongruentes"].append({
            "document": doc,
            "fecha_seeder": expected_start,
            "fecha_real_web": real_start,
            "fecha_fin_calculada": dt_end_real.strftime("%Y-%m-%d"),
            "pdf_url": web_data.get("pdf_url"),
        })
      else:
        print(" ✅ [CORRECTO]")
        dt_start = datetime.strptime(expected_start, "%Y-%m-%d")
        dt_end = dt_start + relativedelta(years=2)

        reporte["correctas"].append({
            "document": doc,
            "fecha_inicio": expected_start,
            "fecha_fin_calculada": dt_end.strftime("%Y-%m-%d"),
            "pdf_url": web_data.get("pdf_url"),
        })

  # --- IMPRESIÓN DEL REPORTE FINAL ---
  print("\n" + "=" * 70)
  print("                      RESUMEN DE AUDITORÍA")
  print("=" * 70 + "\n")

  if reporte["no_existen"]:
    print(
        "❌ RESOLUCIONES QUE NO EXISTEN EN LA WEB"
        f" ({len(reporte['no_existen'])}):\n"
    )
    for item in reporte["no_existen"]:
      print(
          f"   • Documento: {item['document']} (Fecha en seeder:"
          f" {item['date_start']})"
      )
    print()

  if reporte["incongruentes"]:
    print(
        "⚠️ RESOLUCIONES CON FECHA DE INICIO INCORRECTA"
        f" ({len(reporte['incongruentes'])}):\n"
    )
    for item in reporte["incongruentes"]:
      print(f"   • Documento: {item['document']}")
      print(f"     - Fecha en Seeder : {item['fecha_seeder']}")
      print(f"     - Fecha REAL Web  : {item['fecha_real_web']}")
      print(f"     - Nueva Fecha Fin (+2 años): {item['fecha_fin_calculada']}")
      if item["pdf_url"]:
        print(f"     - Enlace PDF      : {item['pdf_url']}")
      print()

  print(f"✅ RESOLUCIONES CORRECTAS: {len(reporte['correctas'])} resoluciones.")
  print("=" * 70)


if __name__ == "__main__":
  auditar()