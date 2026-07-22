; ---------------------------------------------------------------------------
; Configuracion generada automaticamente por SonicStreaming Panel.
; NO editar a mano: se regenera al iniciar/reiniciar la estacion.
; Shoutcast DNAS v2 (sc_serv)
; ---------------------------------------------------------------------------

; Puerto base (usa PORT y PORT+1)
portbase={{PORT}}

; Contrasenas
password={{PASSWORD}}
adminpassword={{ADMIN_PASSWORD}}

; Limites
maxuser={{MAX_LISTENERS}}
streammaxbitrate={{MAX_BITRATE}}

; Identidad de la estacion
streampath_1=/stream
streamid_1=1
publicserver=never

; Metadatos por defecto
streamtitle_1={{STATION_NAME}}
streamgenre_1={{GENRE}}

; Relay (solo si la estacion es de tipo relay)
{{RELAY_LINE}}

; Log
logfile={{LOG_FILE}}
screenlog=0

; Optimizacion de Latencia Ultra-Baja (~5 Segundos de Retraso)
burstonconnect=1
burstsize=16384
autodumpusers=1
autodumpspan=30
