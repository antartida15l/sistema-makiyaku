# Makiyaku

Sistema de riego inteligente para parcelas. Cruza el **clima real de cada parcela** (Open-Meteo) con lo que pide cada **cultivo** y calcula cuándo regar, cuánta agua usar y cuándo proteger de la helada. Makiyaku viene del quechua *maki* (mano) y *yaku* (agua).

Laravel 12 · PHP 8.2+ · MySQL o SQLite · sin paso de compilación para la interfaz (CSS y JS propios en `public/`).

## Puesta en marcha (XAMPP)

```bash
composer install
copy .env.example .env        # en Linux/Mac: cp
php artisan key:generate
# en .env: DB_CONNECTION=mysql, DB_DATABASE=makiyaku, DB_USERNAME=root
php artisan migrate
php artisan db:seed --class=CultivosSierraSeeder   # y los demás seeders de cultivos
php artisan serve
```

Para que el clima y las alertas se actualicen cada 30 minutos deja abierta otra terminal con:

```bash
php artisan schedule:work
```

Sin eso, el panel y la pantalla de alertas actualizan solos (máximo una vez cada 15 minutos).

## Qué incluye

| Módulo | Descripción |
|---|---|
| Panel | Registro "Hoy en el campo" con una gota de agua por parcela, agua usada, alertas, pronóstico de 48 h. |
| Parcelas | Lista con filtros, ficha con plan de riego, GPS propio por parcela, papelera, historial de riegos. |
| Cultivos | Catálogo con filtros y fichas (agua, rango de temperatura, ciclo). |
| Clima | Todo lo de Open-Meteo: actual, 72 horas, 14 días, suelo por profundidad, radiación, UV, viento en altura, CAPE, isoterma 0 °C, calidad del aire, historial de 60 días y comparación con el año anterior, ventanas para regar y fumigar. |
| Alertas | Una escala única de intensidad, una alerta por tema y parcela, sonidos por nivel, volumen ajustable. |
| Recomendaciones | Generadas desde las mismas alertas y el mismo cálculo de agua. Global y por parcela. |
| Reportes | Resumen, agua, alertas, clima y estado de cultivos. En pantalla, imprimible (PDF) y CSV para Excel. |
| Presentación | Video animado con hojas y sonido sintetizado al entrar. También disponible como MP4 en `public/media/`. |
| Perfil | Tema claro/oscuro, sonido, volumen, presentación y hojas flotantes (se guardan en la cuenta). |

## Estructura útil

- `app/Services/OpenMeteoService.php`, `ClimaCompletoService.php`: acceso a Open-Meteo (con caché y respaldo sin internet).
- `app/Services/RiegoService.php`: **única** fórmula de frecuencia y litros de agua.
- `app/Services/AlertaService.php`: motor de alertas. `MonitoreoService.php`: clima + alertas en un paso.
- `app/Services/ReporteService.php`: datos de los reportes.
- `routes/console.php`: tareas programadas.
- `public/css/makiyaku.css`: sistema de diseño (tokens, componentes, modo oscuro).
- `public/js/makiyaku/`: `app.js` (interfaz), `charts.js` (gráficos SVG), `particulas.js` (hojas), `sonidos.js` (alertas), `intro.js` (presentación).
- `resources/views/components/`: `x-icono`, `x-logo`, `x-gota`, `x-campo`, `x-intro`.

## Diseño

Paleta derivada del logo: verde bosque, neblina mineral y noche andina, con un único acento interactivo. Tipografías Bricolage Grotesque (títulos y cifras) y Figtree (interfaz), incluidas en `public/fonts`, sin depender de internet. Sin emojis: iconos SVG propios.

## Pruebas

```bash
php artisan test
```

## Datos meteorológicos

[Open-Meteo](https://open-meteo.com) es gratuito para uso no comercial y no necesita clave. Para uso comercial hay que contratar su plan de pago.
