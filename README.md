# Standalone PHP Solid Server

This project is a standalone Solid Server.

It reuses the PHP libraries from PDS Interop as the basis for the Solid server.
For the user management, no framework is used to keep the codebase lean.

# Project maturity

This project is currently EXPERIMENTAL and should not be used in production yet.

# Installation

Start the docker containers:
```
docker-compose up
```
This will start up two containers: the solid server and a mailpit server. If you have an actual SMTP server running, feel free to remove the mailpit container.

Log into the container (replace 'solid' below with the name of your container). 
```
docker exec -it solid bash
```

Run composer install:
```
cd /opt/solid/
composer install
```

Copy config.php.example to config.php and update the values.
```
cp config.php.example config.php
```

Run init.php to generate the keyset and create the database tables.
```
sudo -u www-data php init.php
```

## DNS gotcha and snake oil certificate

The webIds are created as id-xxxxx.{baseHost}, so in our example, that would be id-xxxx.solid.local.
Storage pods are created as storage-xxxxx.{baseHost}, so that would become storage-xxxx.solid.local.
The snake oil certificate is only for localhost, so accessing this will generate a warning for an invalid certificate; 

You may also need to add these hosts to /etc/hosts to make them available for the browser by pointing them to 127.0.0.1.

# This solid server was built op on these releases:
- pdsinterop/flysystem-rdf (v0.6.0)
- pdsinterop/php-solid-crud (v0.8.0)
- pdsinterop/php-solid-auth (v0.13.0)

# Funding

<p>
  This project was funded through the <a href="https://nlnet.nl/core">NGI0 Core</a> Fund, established by <a href="https://nlnet.nl">NLnet</a> with financial support from the European Commission's <a href="https://ngi.eu">Next Generation Internet</a> programme. 
  Learn more at the <a href="https://nlnet.nl/project/Solid-NC/">NLnet project page</a>
</p>
<p>
  <a href="https://nlnet.nl"><img height="64" alt="NLNet logo" src="https://nlnet.nl/logo/banner.svg"></a>
  <a href="https://nlnet.nl/core"><img height="64" alt="NGI0 Core logo" src="https://nlnet.nl/image/logos/NGI0Core_tag.svg"></a>
  <a href="https://ec.europa.eu/"><img height="64" alt="European Commision logo" src="https://nlnet.nl/image/logos/EC.svg"></a>
</p>
