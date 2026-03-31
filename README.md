# Standalone PHP Solid Server

This project is a standalone Solid Server.

It reuses the PHP libraries from PDS Interop as the basis for the Solid server.
For the user management, no framework is used to keep the codebase lean.

## Installation

Start the docker containers:

```sh
docker-compose up
```

This will start up three containers: the solid server, pubsub server and a mailpit server. If you have an actual SMTP server running, feel free to remove the mailpit container.
The persisted data will be stored in the data/ directory. This contains the keys, pods, db and mailpit data.

Run the following commands to set up the container (replace 'solid' below with the name of your container):
Note: Update the values in the config.php file where needed befure running the init script.

```sh
docker exec -w /opt/solid/ solid cp config.php.example config.php
docker exec -u www-data -i -w /opt/solid/ solid php init.php
docker exec -w /opt/solid/ solid chown -R www-data:www-data keys pods profiles db
```

Now add the following host to your `/etc/hosts` file:
```
127.0.0.1   solid.local
```

And browser to `https://solid.local/`. After you register a new account, you'll get an identity and storage hostname, add these to `/etc/hosts` as well, e.g:
```
127.0.0.1   id-d1f0e8c54e755cb45b61ee8e9dad00fe.solid.local storage-d1f0e8c54e755cb45b61ee8e9dad00fe.solid.local
```

### DNS gotcha and snake oil certificate

The webIds are created as id-xxxxx.{baseHost}, so in our example, that would be id-xxxx.solid.local.
Storage pods are created as storage-xxxxx.{baseHost}, so that would become storage-xxxx.solid.local.
The snake oil certificate is only for localhost, so accessing this will generate a warning for an invalid certificate; 

You may also need to add these hosts to /etc/hosts to make them available for the browser by pointing them to 127.0.0.1.

### This solid server was built op on these releases:
- pdsinterop/flysystem-rdf (v0.6.0)
- pdsinterop/php-solid-crud (v0.8.1)
- pdsinterop/php-solid-auth (v0.13.0)

## Contributing

Questions or feedback can be given by [opening an issue on GitHub][issues-link].

All PDS Interop projects are open source and community-friendly.
Any contribution is welcome!
For more details read the [contribution guidelines][contributing-link].

All PDS Interop projects adhere to [the Code Manifesto](http://codemanifesto.com)
as its [code-of-conduct][code-of-conduct]. Contributors are expected to abide by its terms.

There is [a list of all contributors on GitHub][contributors-page].

For a list of changes see the [the GitHub releases page][releases-page].

## License

All code created by PDS Interop is licensed under the [MIT License][license-link].

## Funding

<p>
  This project was funded through the <a href="https://nlnet.nl/core">NGI0 Core</a> Fund, established by <a href="https://nlnet.nl">NLnet</a> with financial support from the European Commission's <a href="https://ngi.eu">Next Generation Internet</a> programme. 
  Learn more at the <a href="https://nlnet.nl/project/Solid-NC/">NLnet project page</a>
</p>
<p>
  <a href="https://nlnet.nl"><img height="64" alt="NLNet logo" src="https://nlnet.nl/logo/banner.svg"></a>
  <a href="https://nlnet.nl/core"><img height="64" alt="NGI0 Core logo" src="https://nlnet.nl/image/logos/NGI0Core_tag.svg"></a>
  <a href="https://ec.europa.eu/"><img height="64" alt="European Commision logo" src="https://nlnet.nl/image/logos/EC.svg"></a>
</p>

[code-of-conduct]: https://pdsinterop.org/code-of-conduct/
[contributing-link]: https://pdsinterop.org/contributing/
[contributors-page]: https://github.com/pdsinterop/multiuser-php-solid-server/contributors
[issues-link]: https://github.com/pdsinterop/multiuser-php-solid-server//issues
[license-link]: https://pdsinterop.org/license/
[releases-page]: https://github.com/pdsinterop/multiuser-php-solid-server/releases
