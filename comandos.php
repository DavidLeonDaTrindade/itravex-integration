// # ============================================
// # GIATA SYNC PROPERTIES (SYSGIATA)
// # ============================================
abbey_lc
abbey_tp
allbeds
alturadestinationservices
ask2travel
babylon_holiday
barcelo
cn_travel
connectycs
darinaholidays
DOTW
dts_dieuxtravelservices
gekko_infinite
gekko_teldar
guestincoming
hotelbook
hyperguest
iol_iwtx
itravex
logitravel_dr
methabook2
mikitravel
opentours
ors_beds
paximum
ratehawk2
restel
solole
sunhotels
travellanda
veturis
w2m
wl2t
yalago
// * --------------------------------------------
// * 1) Providers por defecto (si no se pasa --provider)
// * --------------------------------------------
$defaultProviders = [
    // ! OJO: mantener en minúsculas para evitar mismatch
    'babylon_holiday',
    'barcelo',
    'cn_travel',
    // ...
];

// * --------------------------------------------
// * 2) Flags / opciones del comando
// * --------------------------------------------
$doEnrich     = (bool)$this->option('enrich-basics');
$onlyActive   = (bool)$this->option('only-active');
$saveCodes    = (bool)$this->option('save-codes');
$refreshCodes = (bool)$this->option('refresh-codes');
$sleepMs      = (int)($this->option('sleep') ?? 100);

// ? ¿Queremos forzar siempre name/country si ya hay detailXml?
// TODO: Considerar mover enriquecimiento a un comando separado

// * --------------------------------------------
// * 3) Auth + cliente HTTP GIATA
// * --------------------------------------------
$base = rtrim(config('services.giata.base_url', env('GIATA_BASE_URL', 'https://multicodes.giatamedia.com/webservice/rest/1.0')), '/');
// ! Si faltan credenciales -> abortar

// * --------------------------------------------
// * 3)MYSQL
// * --------------------------------------------
-- * --------------------------------------------
-- * Duplicados en giata_property_codes
-- * --------------------------------------------
SELECT g.*
FROM giata_property_codes g
JOIN (
    SELECT giata_property_id, provider_id, code_value
    FROM giata_property_codes
    GROUP BY giata_property_id, provider_id, code_value
    HAVING COUNT(*) > 1
) d
ON g.giata_property_id = d.giata_property_id
AND g.provider_id = d.provider_id
AND g.code_value = d.code_value
ORDER BY g.giata_property_id, g.provider_id, g.code_value, g.id;

-- * --------------------------------------------
-- * Total registros en giata_property_codes
-- * --------------------------------------------
SELECT COUNT(*) AS total_registros
FROM giata_property_codes;

-- * --------------------------------------------
-- * Total registros 1 unico proveedor
-- * --------------------------------------------
SELECT COUNT(*) AS total_active_codes
FROM giata_property_codes gpc
JOIN giata_providers gp ON gp.id = gpc.provider_id
WHERE gp.provider_code = 'itravex'
  AND (gpc.status IS NULL OR gpc.status = 'active');


// * --------------------------------------------
// * 3)COMANDO ENRIQUECER BASE DE DATOS
// * --------------------------------------------
# * Sync provider iol_iwtx (solo activos), guarda codes y también name/country
docker exec -it laravel_app php -d memory_limit=1024M artisan giata:sync-properties \
  --provider=iol_iwtx \
  --only-active \
  --save-codes \
  --providers=iol_iwtx \
  --refresh-codes \
  --enrich-basics \
  --sleep=100
