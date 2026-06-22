<?php
error_reporting(E_ALL & ~E_NOTICE);

// ==========================================
// CONFIGURACIÓN INTERNA Y AUTOMATIZADA
// ==========================================
// Pon aquí las credenciales fijas de tu sistema
define('CORREO_RECEPTOR', 'correo.automatizado.yallegue@gmail.com'); 
define('PASSWORD_APP', 'omhn sbzo ycra nwry'); // Tu contraseña de aplicación de 16 dígitos
define('SERVIDOR_IMAP', 'imap.gmail.com');

// El remitente específico que estamos auditando
define('REMITENTE_OBJETIVO', 'sayros364@gmail.com');

$resultado = "";
$error = "";
$datos_extraidos = null; // Aquí guardaremos el objeto con la información limpia

// El proceso se activa al pulsar el botón de "Sincronizar"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_sincronizar'])) {
    
    $mailbox = "{" . SERVIDOR_IMAP . ":993/imap/ssl}INBOX";
    $inbox = @imap_open($mailbox, CORREO_RECEPTOR, PASSWORD_APP);
    
    if (!$inbox) {
        $error = "Error de autenticación interna: " . imap_last_error();
    } else {
        // Buscar correos del remitente objetivo
        $emails = imap_search($inbox, 'FROM "' . REMITENTE_OBJETIVO . '"');
        
        if ($emails) {
            rsort($emails); // Últimos correos primero
            $ultimo_correo = $emails[0]; // Tomamos solo el más reciente
            
            $overview = imap_fetch_overview($inbox, $ultimo_correo, 0);
            $estructura = imap_fetchstructure($inbox, $ultimo_correo);
            $cuerpo = imap_fetchbody($inbox, $ultimo_correo, 1);
            
            // Decodificación Base64 si aplica
            if (isset($estructura->parts[0]) && $estructura->parts[0]->encoding == 3) {
                $cuerpo = base64_decode($cuerpo);
            } elseif (isset($estructura->encoding) && $estructura->encoding == 3) {
                $cuerpo = base64_decode($cuerpo);
            }
            
            // Buscar el patrón del código (REF-XXXX)
            preg_match('/REF-[0-9]+/i', $cuerpo, $coincidencias);
            $codigo = !empty($coincidencias) ? $coincidencias[0] : "No detectado";
            
            // ==========================================================
            // LOGICA DE RECOLECCIÓN (La información ya está capturada aquí)
            // ==========================================================
            $datos_extraidos = [
                'remitente' => REMITENTE_OBJETIVO,
                'fecha_correo' => isset($overview[0]->date) ? $overview[0]->date : date('Y-m-d H:i:s'),
                'asunto' => isset($overview[0]->subject) ? $overview[0]->subject : 'Sin asunto',
                'codigo_verificacion' => $codigo
            ];
            
            // Construcción del mensaje de éxito para la interfaz
            $resultado = "Lectura completada con éxito.";
            
        } else {
            $error = "No se encontraron correos nuevos pendientes de " . REMITENTE_OBJETIVO;
        }
        imap_close($inbox);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Automatización IMAP</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-slate-900 min-h-screen font-sans flex items-center justify-center p-4 text-slate-100">

    <div class="w-full max-w-lg bg-slate-800 rounded-2xl shadow-xl p-6 border border-slate-700">
        <div class="mb-6 text-center">
            <h1 class="text-xl font-bold text-white tracking-tight">Extractor de Códigos Automatizado</h1>
            <p class="text-sm text-slate-400 mt-1">Escaneando remitente: <span class="text-indigo-400 font-mono"><?php echo REMITENTE_OBJETIVO; ?></span></p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-5 p-4 bg-red-950/50 text-red-400 rounded-xl border border-red-800/50 text-sm">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="mb-6">
            <input type="hidden" name="accion_sincronizar" value="1">
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-medium py-3 px-4 rounded-xl transition duration-150 text-sm shadow-md flex items-center justify-center gap-2 cursor-pointer">
                <span>Escanear y Procesar Último Correo</span> 🔄
            </button>
        </form>

        <?php if ($datos_extraidos): ?>
            <div class="border-t border-slate-700 pt-5 space-y-3">
                <div class="bg-slate-850 p-4 rounded-xl border border-slate-700/60 space-y-2 text-sm">
                    <div class="flex justify-between border-b border-slate-700 pb-2">
                        <span class="text-slate-400">Origen:</span>
                        <span class="font-medium text-slate-200"><?php echo htmlspecialchars($datos_extraidos['remitente']); ?></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-700 pb-2">
                        <span class="text-slate-400">Fecha Correo:</span>
                        <span class="text-slate-300"><?php echo htmlspecialchars($datos_extraidos['fecha_correo']); ?></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-700 pb-2">
                        <span class="text-slate-400">Asunto:</span>
                        <span class="text-slate-300 truncate max-w-[220px]"><?php echo htmlspecialchars($datos_extraidos['asunto']); ?></span>
                    </div>
                    <div class="flex justify-between items-center pt-1">
                        <span class="text-slate-400 font-semibold">Código Detectado:</span>
                        <span class="px-3 py-1 bg-emerald-500/10 text-emerald-400 font-mono font-bold rounded-lg border border-emerald-500/20 text-base">
                            <?php echo htmlspecialchars($datos_extraidos['codigo_verificacion']); ?>
                        </span>
                    </div>
                </div>
                <p class="text-xs text-center text-emerald-400 animate-pulse">✓ Información parseada y lista en memoria del servidor.</p>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
