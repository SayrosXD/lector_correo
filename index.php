<?php
// 1. Control de errores y variables iniciales
error_reporting(E_ALL & ~E_NOTICE);
$resultado = "";
$error = "";

// 2. Procesar el formulario cuando se hace la petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo_receptor = $_POST['correo_receptor'] ?? '';
    $password_app = $_POST['password_app'] ?? '';
    $servidor_imap = $_POST['servidor_imap'] ?? 'imap.gmail.com';
    
    if (empty($correo_receptor) || empty($password_app)) {
        $error = "Por favor, completa tu correo receptor y la contraseña de aplicación.";
    } else {
        // Estructura de la cadena de conexión IMAP segura (Puerto 993 SSL)
        $mailbox = "{" . $servidor_imap . ":993/imap/ssl}INBOX";
        
        // Intentar conectar al servidor de correo
        $inbox = @imap_open($mailbox, $correo_receptor, $password_app);
        
        if (!$inbox) {
            $error = "Fallo en la conexión: " . imap_last_error();
        } else {
            // Filtrar la búsqueda únicamente para correos provenientes de tu remitente de pruebas
            $remitente_objetivo = "sayros364@gmail.com";
            $emails = imap_search($inbox, 'FROM "' . $remitente_objetivo . '"');
            
            if ($emails) {
                // Ordenar los correos para mostrar los más recientes primero
                rsort($emails);
                
                $resultado .= "<h3 class='text-lg font-bold text-emerald-700 mb-4'>✅ Conexión exitosa. Correos de: " . htmlspecialchars($remitente_objetivo) . "</h3>";
                $resultado .= "<div class='space-y-4'>";
                
                // Analizar únicamente los últimos 3 mensajes para la prueba
                $ultimos_correos = array_slice($emails, 0, 3);
                
                foreach ($ultimos_correos as $id_correo) {
                    $overview = imap_fetch_overview($inbox, $id_correo, 0);
                    $estructura = imap_fetchstructure($inbox, $id_correo);
                    $cuerpo = imap_fetchbody($inbox, $id_correo, 1);
                    
                    // Manejar la decodificación si el correo viene en Base64
                    if (isset($estructura->parts[0]) && $estructura->parts[0]->encoding == 3) {
                        $cuerpo = base64_decode($cuerpo);
                    } elseif (isset($estructura->encoding) && $estructura->encoding == 3) {
                        $cuerpo = base64_decode($cuerpo);
                    }
                    
                    // Expresión regular para buscar el patrón del código de verificación
                    preg_match('/REF-[0-9]+/i', $cuerpo, $coincidencias);
                    $codigo_detectado = !empty($coincidencias) ? $coincidencias[0] : "No se encontró ningún patrón 'REF-XXXX'";
                    
                    // Construir la tarjeta HTML de forma segura
                    $resultado .= "<div class='p-4 bg-white rounded-lg border border-gray-200 shadow-sm'>";
                    $resultado .= "<p class='text-sm text-gray-500'><b>Fecha:</b> " . (isset($overview[0]->date) ? htmlspecialchars($overview[0]->date) : 'N/A') . "</p>";
                    $resultado .= "<p class='text-base font-semibold text-gray-800 mt-1'><b>Asunto:</b> " . (isset($overview[0]->subject) ? htmlspecialchars($overview[0]->subject) : 'Sin asunto') . "</p>";
                    $resultado .= "<p class='text-sm text-gray-600 mt-2 bg-gray-50 p-2 rounded'><b>Texto extraído:</b> " . htmlspecialchars(substr(strip_tags($cuerpo), 0, 150)) . "...</p>";
                    $resultado .= "<div class='mt-3'><span class='inline-block px-3 py-1 bg-indigo-100 text-indigo-800 text-xs font-bold rounded-full'>Analizador de código: " . htmlspecialchars($codigo_detectado) . "</span></div>";
                    $resultado .= "</div>";
                }
                
                $resultado .= "</div>";
            } else {
                $resultado = "<div class='p-4 bg-amber-50 text-amber-800 rounded-lg border border-amber-200'>Conectado correctamente a la cuenta, pero no se encontraron correos en el INBOX que vengan de <b>" . htmlspecialchars($remitente_objetivo) . "</b>.</div>";
            }
            
            // Cerrar la conexión de forma limpia
            imap_close($inbox);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PoC Lector IMAP - Render</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans flex items-center justify-center p-4">
    <div class="w-full max-w-2xl bg-white rounded-xl shadow-md p-6 md:p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Prueba de Concepto: Lector IMAP</h1>
        <p class="text-sm text-gray-500 mb-6">Este script leerá las alertas entrantes buscando mensajes específicos.</p>
        
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 bg-red-50 text-red-800 rounded-lg border border-red-200 text-sm font-medium">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tu Correo Receptor (Donde recibes el mensaje)</label>
                    <input type="email" name="correo_receptor" required placeholder="ejemplo@gmail.com"
                           value="<?php echo htmlspecialchars($_POST['correo_receptor'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña de Aplicación de 16 dígitos</label>
                    <input type="password" name="password_app" required placeholder="xxxx xxxx xxxx xxxx"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Servidor IMAP del Proveedor</label>
                <select name="servidor_imap" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                    <option value="imap.gmail.com" <?php echo (($_POST['servidor_imap'] ?? '') === 'imap.gmail.com') ? 'selected' : ''; ?>>Gmail (imap.gmail.com)</option>
                    <option value="outlook.office365.com" <?php echo (($_POST['servidor_imap'] ?? '') === 'outlook.office365.com') ? 'selected' : ''; ?>>Outlook / Hotmail (outlook.office365.com)</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md transition duration-200 text-sm shadow-sm">
                Consultar Bandeja de Entrada 🔄
            </button>
        </form>

        <?php if (!empty($resultado)): ?>
            <div class="border-t border-gray-200 pt-6">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <?php echo $resultado; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
