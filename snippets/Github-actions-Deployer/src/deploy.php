<?php
namespace Deployer;

require 'recipe/laravel.php';
require 'contrib/rsync.php';

// Hosts
host('example.com') // Name of the server
    ->setHostname('example.com') // Hostname or IP address
    ->setPort(22)
    ->set('http_user', 'username') // SSH-username
    ->setRemoteUser('username') // SSH-username
    ->setDeployPath('/var/www/example.com/production')
    ->setLabels(['stage' => 'production'])
    ->set('writable_mode', 'chmod')
//    ->set('bin/php', '/opt/alt/php74/usr/bin/php') // May be set to 'php' to use servers default alias
    ->set('rsync_src', __DIR__)
    ->set('keep_releases', 3)
    ->set('ssh_multiplexing', true);

// Configuring the rsync exclusions.
add('rsync', [
    'exclude' => [
        '/.env',
        '/.git',
        '/.github',
        '/node_modules',
        '/storage',
        '/vendor',
        '/deploy.php',
    ],
]);

// Tasks
after('deploy:failed', 'deploy:unlock');

task('deploy:secrets', function () {
    file_put_contents(__DIR__ . '/.env', getenv('DOT_ENV'));
    upload('.env', get('deploy_path') . '/shared');
});

desc('Deploy the application');
task('deploy', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'rsync', // Deploy code & built assets
    'deploy:secrets', // Deploy secrets
    'deploy:shared',
    'deploy:vendors',
    'deploy:writable',
    'artisan:storage:link', // |
    'artisan:view:cache',   // |
    'artisan:config:cache', // | Laravel specific steps
    'artisan:optimize',     // |
    'artisan:migrate',      // |
    'deploy:symlink',
    'deploy:unlock',
    'deploy:cleanup',
]);
