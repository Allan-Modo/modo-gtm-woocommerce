<?php
namespace ModoGtmWc\Core;

if (!defined('ABSPATH')) exit;

/**
 * Autoloader minimal pour l'espace de noms du plugin.
 *
 * Fait correspondre les classes du namespace `ModoGtmWc\` aux fichiers du dossier `app/`
 * en suivant une convention simple de type PSR-4 :
 *   ModoGtmWc\Foo\Bar => app/Foo/Bar.php
 */
class Autoloader {
    /**
     * Enregistre la fonction d'autoload auprès de SPL.
     */
    public static function register(): void {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Tente de charger le fichier de classe s'il appartient à notre namespace.
     */
    private static function autoload(string $class): void {
        $prefix = 'ModoGtmWc\\';
        // Répertoire de base pour le préfixe de namespace (pointe vers app/)
        $base_dir = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR;

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return; // Pas notre namespace
        }

        // Remplace les séparateurs de namespace par des séparateurs de dossier et ajoute .php
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
}
