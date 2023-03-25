## Automatic deployment of a Laravel application using Github Actions and Deployer 7.0

[Deployer](https://deployer.org) is a great tool for deploying all kind of applications.
In this tutorial we will combine it with Github Actions so we can setup automatic deployment.

At this point we assume you have a Github-account with a repository (it may be private) containing your Laravel project. \
It's okay if you have a shared hosting, as long as you have SSH-access to it.

1. First of all, you need 2 files. Add them to your project and check/alter the settings:
   - [/.github/workflows/deploy.yml](./src/.github/workflows/deploy.yml) (create folder `/.github/workflows` for this)
   - [deploy.php](./src/deploy.php)

2. Run the following command in your project:

    `composer require deployer/deployer --dev`

   *You may use `composer require deployer/dist --dev` but I preferer deployer/deployer because it is easier to debug/work with.*

3. In your Github repository go to Settings > Secrets > Actions

   Here we add 2 repository secrets:
   - DOT_ENV
     
     `With the contents of your production-.env`
   
   - SSH_PRIVATE_KEY

      Generate a (temporary) private SSH key, this may be done on your local machine or on the server itself.
      \
      \
      If you use Linux, run:
   
      `ssh-keygen -t rsa -b 4096 -C "your_email@example.com"`

      It's recommended to use a filename like `github_actions` for this key.

      Leave the passphrase empty.
      \
      \
      The content of the new file with the name `github_actions` must be stored in the `SSH_PRIVATE_KEY` setting.
      It starts with `-----BEGIN RSA PRIVATE KEY-----` 
      \
      \
      The content of `github_actions.pub` must be added to your server.\
      If you use Plesk or DirectAdmin this can be added there. It starts with `ssh-rsa `.

     
     If you use Windows, you could use PuTTYgen to generate a keypair
     but it may be easier to do this from your Linux-server.    

5. On the filesystem of your server inside the folder as set in the `setDeployPath`-setting  of `deploy.php`, create the following folders:

   - /shared/storage/app/public
   - /shared/storage/framework/cache
   - /shared/storage/framework/sessions
   - /shared/storage/framework/views

6. Set the document root on your server to `/var/www/example.com/production/current/public` \
   (corresponding to the `setDeployPath`-setting of `deploy.php` followed with `/current/public`)
   
7. Push to your `production`-branch or merge a pull-request into it, in the Actions-tab of Github you should see an active workflow now. 


### Sources
- Highly insipred by https://atymic.dev/blog/github-actions-laravel-ci-cd/
- With a lot of help of https://zellwk.com/blog/github-actions-deploy/

### Deploying to different/multiple environments
When you want to deploy to multiple environments, for example to a production- and an acceptation-environment, only a small extention is required:

1. In your [deploy.php](./src/deploy.php) duplicate all host configuration lines, change the label of the new block to `acception` and change the DeployPath for example.

2. In [/.github/workflows/deploy.yml](./src/.github/workflows/deploy.yml) change the last line from `dep: deploy` into `dep deploy stage=production`

3. Duplicate [/.github/workflows/deploy.yml](./src/.github/workflows/deploy.yml), save it for example as `deploy-acceptation.yml` and change:
```
on:
  push:
    branches: production
```
into:

```
on:
  push:
    branches: acceptation
```
and change the last line into: `dep deploy stage=acceptation`

4. Create a new branch called `acceptation`, as soon as you merge into this branch Github Actions will deploy into the path specified in the new host-configration.

5. If you want to deploy to different servers, you have to create new Github Secrets (Settings > Secrets > Actions) like `SSH_PRIVATE_KEY_ACC` and `SSH_KNOWN_HOSTS_ACC` and configure these in your new `deploy-acceptation.yml`.

### Running tests in Github Actions
The `deploy.yml`-file can be extended with tests which can run at the same time as as the `build-js`-step
which will make shure your application only deploys when all tests succeed.
To make this work, add this above `deploy:`
   ```
   test-php:
   name: PHPUnit tests
   runs-on: ubuntu-latest
   steps:
   - uses: actions/checkout@v3
     - name: Setup PHP
     uses: shivammathur/setup-php@master
     with:
     php-version: 8.2
     extensions: mbstring, bcmath
     - name: Composer install
     run: composer install
     - name: Run Tests
     run: ./vendor/bin/phpunit
   ```
and change `  needs: build-js` into `  needs: [build-js, test-php]`.

The first 2 jobs will run at the same time, the `deploy`-job will start after both are finished.\
If the PHPUnit tests fail, the new version will not be deployed.
