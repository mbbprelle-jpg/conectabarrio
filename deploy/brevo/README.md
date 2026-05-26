# Brevo (SMTP) + ConectaBarrio

ConectaBarrio envía correo con **PHPMailer** usando el relay SMTP de Brevo. No hace falta Mailcow ni puertos de correo en el VPS.

Documentación Brevo: [SMTP relay](https://developers.brevo.com/docs/send-a-transactional-email)

---

## 1. Verificar remitente en Brevo

En [Brevo](https://app.brevo.com/) → **Remitentes, dominios y dedicados** → añade y valida el correo que usarás como remitente, por ejemplo:

`contacto@conectatubarrio.cl`

`SMTP_FROM_EMAIL` debe ser **exactamente** un remitente verificado; si no, Brevo rechaza el envío.

---

## 2. Credenciales SMTP (ya creadas en tu cuenta)

| Campo | Valor |
|-------|--------|
| Servidor | `smtp-relay.brevo.com` |
| Puerto | `587` |
| Cifrado | TLS (STARTTLS) |
| Usuario / login | `ac86ca001@smtp-brevo.com` |
| Contraseña | La **clave SMTP** (valor secreto), no el “nombre de la clave” |

La contraseña es la que copiaste al crear la clave en Brevo (suele empezar por caracteres alfanuméricos largos). Si la perdiste, genera una **nueva clave SMTP** en el panel.

---

## 3. Variables en Coolify (producción)

Servicio **ConectaBarrio** → **Environment Variables**:

```env
SMTP_HOST=smtp-relay.brevo.com
SMTP_PORT=587
SMTP_USER=ac86ca001@smtp-brevo.com
SMTP_PASS=PEGAR_AQUI_LA_CLAVE_SMTP_SECRETA
SMTP_FROM_EMAIL=contacto@conectatubarrio.cl
SMTP_FROM_NAME=ConectaBarrio
SMTP_ENCRYPTION=tls
```

Guarda y **redeploy** la aplicación.

`SMTP_PASS` nunca se sube al repositorio; solo en Coolify (o en tu `.env` local ignorado por git).

---

## 4. XAMPP (desarrollo local)

Opción A — variables de entorno en Windows (sesión actual):

```powershell
$env:SMTP_HOST="smtp-relay.brevo.com"
$env:SMTP_PORT="587"
$env:SMTP_USER="ac86ca001@smtp-brevo.com"
$env:SMTP_PASS="tu_clave_smtp"
$env:SMTP_FROM_EMAIL="contacto@conectatubarrio.cl"
$env:SMTP_FROM_NAME="ConectaBarrio"
$env:SMTP_ENCRYPTION="tls"
```

Opción B — copiar `.env.example` a `.env` en la raíz del proyecto (`.env` está en `.gitignore` si lo añades).

---

## 5. Probar

1. Inicia sesión como admin con plan que permita envío de balances.
2. Envía un balance de prueba a un correo tuyo (Gmail).
3. Si falla, en Brevo revisa **Transactional → Logs** y el mensaje de error en la app.

Errores frecuentes:

| Error | Causa |
|-------|--------|
| Authentication failed | `SMTP_PASS` incorrecta o clave revocada |
| Sender not valid | `SMTP_FROM_EMAIL` no verificado en Brevo |
| Daily limit | Plan gratuito con límite diario alcanzado |

---

## 6. DNS (opcional, mejor entregabilidad)

En el dominio `conectatubarrio.cl`, Brevo indica registros **SPF/DKIM** en **Dominios**. Configúralos en tu registrador para que los correos no caigan en spam.

No necesitas MX propio ni servidor de correo en `178.105.195.221`.
