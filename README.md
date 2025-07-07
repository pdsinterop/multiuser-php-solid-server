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
This will start up three containers: the solid server, pubsub server and a mailpit server. If you have an actual SMTP server running, feel free to remove the mailpit container.
The persisted data will be stored in the data/ directory. This contains the keys, pods, db and mailpit data.

Run the following commands to set up the container (replace 'solid' below with the name of your container):
Note: Update the values in the config.php file where needed befure running the init script.

```
docker exec -w /opt/solid/ solid cp config.php.example config.php
docker exec -u www-data -i -w /opt/solid/ solid php init.php
docker exec -w /opt/solid/ solid chown -R www-data:www-data keys pods db
```

## DNS gotcha and snake oil certificate

The webIds are created as id-xxxxx.{baseHost}, so in our example, that would be id-xxxx.solid.local.
Storage pods are created as storage-xxxxx.{baseHost}, so that would become storage-xxxx.solid.local.
The snake oil certificate is only for localhost, so accessing this will generate a warning for an invalid certificate; 

You may also need to add these hosts to /etc/hosts to make them available for the browser by pointing them to 127.0.0.1.

# This solid server was built op on these releases:
- pdsinterop/flysystem-rdf (v0.6.0)
- pdsinterop/php-solid-crud (v0.8.1)
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
