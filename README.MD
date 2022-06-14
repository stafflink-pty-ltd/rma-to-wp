# Rate My Agent To WordPress
A plugin designed to import and display reviews and properties from ratemyagent.com.au on a WordPress Website.

[www.clientname.com.au](https://stafflink.com.au)

## Requirements/Last Tested With
- [WordPress >= 6.0](https://wordpress.org/)
- [NodeJS >= 14.19.2](https://nodejs.org/en/)
- [npm >= 6.14.17](https://www.npmjs.com/)
- [PHP >= 7.4](https://www.php.net/)

_Apple M1 CHIP: node-sass is not supported on ARM architecture and may require you to downgrade node to a lower version of NodeJS to run certain features ~v14._

## How It Works
Imports properties via the Rate My Agent API [Docs are here](https://developers.ratemyagent.com.au/index.html).

## Local Dev Setup
Clone the repo `git clone git@github.com:stafflink-pty-ltd/rma-to-wp.git`

Install Dependancies `npm install`

Compile SCSS `npm run scss`

## How to use:
- In your WP Admin section, navigate to Settings -> RMA to WP
- Add your Rate My Agent Client ID, Secret Key, and Agent/Agency ID. 
- Save.

## Deployment Pipeline
Production: `master`.

If you want to create new features, create a branch, add your feature, then push for review then pull request.