# Mailcow + ConectaBarrio (alternativa — no usado)

> **El proyecto usa [Brevo](../brevo/README.md) para envío de correo.** Esta guía queda como referencia si más adelante quieres correo propio en el VPS.

# Mailcow + ConectaBarrio (Coolify)

Servidor objetivo: Ubuntu en `178.105.195.221` con **Coolify v4** y dominio **conectatubarrio.cl**.

Mailcow se instala **en el host** (`/opt/mailcow-dockerized`), no como app PHP dentro de Coolify. ConectaBarrio solo se conecta por **SMTP** (PHPMailer).

---

## Arquitectura

```
Internet
   │
   ├─ app.conectatubarrio.cl  ──► Coolify ──► contenedor ConectaBarrio (PHP)
   │
   └─ mail.conectatubarrio.cl ──► Mailcow (SMTP 587, IMAP 993, webmail, panel)
         ▲
         └── ConectaBarrio envía correo aquí (SMTP_USER = buzón creado en Mailcow)
```

---

## 1. DNS (registrador del dominio)

Sustituye `conectatubarrio.cl` si usas otro dominio.

| Tipo | Nombre | Valor | Notas |
|------|--------|-------|--------|
| A | `mail` | `178.105.195.221` | Hostname de Mailcow |
| MX | `@` | `mail.conectatubarrio.cl` | Prioridad `10` |
| TXT | `@` | `v=spf1 mx a:mail.conectatubarrio.cl -all` | Ajustar tras crear DKIM en Mailcow |
| TXT | `dkim._domainkey` | *(copiar del panel Mailcow)* | Configuration → Configuration & Details → DKIM |
| TXT | `_dmarc` | `v=DMARC1; p=quarantine; rua=mailto:postmaster@conectatubarrio.cl` | Opcional al inicio |

**PTR (rDNS)** en el proveedor del VPS: que `178.105.195.221` apunte a `mail.conectatubarrio.cl`. Sin PTR, muchos correos caen en spam.

---

## 2. Puertos y conflicto con Coolify

Coolify suele usar **80 y 443** en el host. Mailcow también los usa por defecto.

**Recomendación en un solo VPS:** dejar 80/443 para Coolify y publicar la UI de Mailcow en puertos alternativos (ver `mailcow.conf.snippet`):

- Panel / webmail: `https://mail.conectatubarrio.cl:8443`
- SMTP submission: **587** (obligatorio para la app)
- SMTPS: **465**
- IMAP: **993**
- SMTP entrante (MX): **25**

Abrir en firewall (UFW o panel del proveedor):

`25, 465, 587, 993, 8443` (y `8080` si usas HTTP alternativo).

---

## 3. Instalar Mailcow en el servidor (SSH)

Conéctate al VPS (`178.105.195.221`) como root o con sudo.

```bash
apt update
apt install -y git openssl curl gawk coreutils grep jq

# Docker (si Coolify ya lo instaló, verifica versión >= 24)
docker --version
docker compose version

umask 0022
cd /opt
git clone https://github.com/mailcow/mailcow-dockerized
cd mailcow-dockerized
./generate_config.sh
```

Cuando pida el hostname, usa exactamente:

```
mail.conectatubarrio.cl
```

Edita `mailcow.conf` (puedes basarte en `mailcow.conf.snippet` de esta carpeta):

```bash
nano mailcow.conf
```

Arranque:

```bash
docker compose pull
docker compose up -d
```

Espera 2–5 minutos. Panel:

- URL: `https://mail.conectatubarrio.cl:8443/admin` (si cambiaste puertos HTTPS)
- Usuario: `admin`
- Contraseña inicial: `moohoo` → **cámbiala de inmediato**

---

## 4. Crear buzones en Mailcow

En el panel: **Email → Mailboxes → Add mailbox**

Ejemplo para ConectaBarrio:

| Campo | Valor |
|-------|--------|
| Username | `contacto` |
| Domain | `conectatubarrio.cl` |
| Password | *(clave segura)* |

Correo completo: `contacto@conectatubarrio.cl`

Puedes crear más buzones (`noreply@`, `admin@`, etc.) para distintos usos.

---

## 5. Variables SMTP en Coolify (app ConectaBarrio)

En el servicio de ConectaBarrio → **Environment Variables**:

```env
SMTP_HOST=mail.conectatubarrio.cl
SMTP_PORT=587
SMTP_USER=contacto@conectatubarrio.cl
SMTP_PASS=LA_CLAVE_DEL_BUZON
SMTP_FROM_EMAIL=contacto@conectatubarrio.cl
SMTP_FROM_NAME=ConectaBarrio
SMTP_ENCRYPTION=tls
```

**No uses `localhost`** dentro del contenedor de Coolify: no apunta al Mailcow del host.

Si `mail.conectatubarrio.cl` no resuelve desde el contenedor, prueba temporalmente:

```env
SMTP_HOST=178.105.195.221
```

(mejor solución definitiva: DNS interno o nombre `mail.conectatubarrio.cl` resoluble desde Docker).

Redeploy de ConectaBarrio tras guardar variables.

---

## 6. Probar envío

1. En Mailcow: **Email → Queue** (ver si hay correos atascados).
2. En ConectaBarrio: enviar un balance de prueba a un correo externo (Gmail).
3. Si falla, revisar logs del contenedor PHP y en Mailcow **Logs → Postfix**.

Prueba SMTP desde el servidor (opcional):

```bash
apt install -y swaks
swaks --to tu@gmail.com \
  --from contacto@conectatubarrio.cl \
  --server mail.conectatubarrio.cl:587 \
  --auth LOGIN \
  --auth-user contacto@conectatubarrio.cl \
  --auth-password 'TU_CLAVE' \
  --tls
```

---

## 7. Actualizar SPF/DKIM tras Mailcow

En Mailcow: **Configuration → Configuration & Details → ARC/DKIM keys** → copiar registro DKIM al DNS.

Ajustar SPF si Mailcow indica otra cadena recomendada.

---

## 8. Mantenimiento

```bash
cd /opt/mailcow-dockerized
docker compose pull
docker compose up -d
```

Backup: volúmenes Docker de mailcow (`docker volume ls | grep mailcow`).

---

## 9. XAMPP local (opcional)

No instales Mailcow en Windows. Para desarrollo local:

- Usa las mismas variables apuntando a `mail.conectatubarrio.cl:587`, o
- Crea un buzón de prueba y conéctate al SMTP del VPS.

---

## Referencias

- [Instalación Mailcow](https://docs.mailcow.email/getstarted/install/)
- [Reverse proxy](https://docs.mailcow.email/post_installation/reverse-proxy/) (si más adelante unificas 443 con Traefik/Coolify)
