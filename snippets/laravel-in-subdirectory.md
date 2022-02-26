## How to install Laravel in a subfolder/subdirectory of a document root (on Apache)
Although some will advise against this, it is possible to install Laravel in a non-rootfolder, but not out-of-the-box.
There are many other ways to do this, but I think this one is the easiest.

This tutorial assumes you have a website, lets call it `example.com` and subfolder named `subfolder` with your Laravel-application.
The goal is to have a working Laravel-app when visiting `example.com/subfolder`.

1. In your `.env`, add:

    `ASSET_URL=/subfolder`
   
    This will fix the asset-paths for your images, CSS- and JS-files.

2. This may sound a little contradictory, but you have to set `APP_URL=https://example.com/subfolder` including the `/subfolder` as well.

3. We will use this variable in order the fix the routing, in your `app/Providers/RouteServiceProvider.php` add `config('app.asset_url')` to the route-prefixes.

   So change this:
    ```
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }
    ```
    into:
    ```
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix(config('app.asset_url') . '/api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::prefix(config('app.asset_url') . '/')
                ->middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }
    ```
    This won't hurt if you don't specify an `ASSET_URL` in your .env (for you local dev-environment for example).

4. Finally the tricky part and the main reason for the advises against this workaround: in order to show the Laravel-app you need to point to the `/public`-folder.
   This can be done with a `.htaccess` in the root of your Laravel-project (so in `/subfolder`):
   ```
   <IfModule mod_rewrite.c>
        RewriteEngine on
        RewriteRule (.*) public/$1 [L]
    </IfModule>
   ```


I hope you found this helpfull, if you have any questions or suggestions: please let me know!