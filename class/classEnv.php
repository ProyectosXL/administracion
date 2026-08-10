<?php


class DotEnv
{
    /**
     * The directory where the .env file can be located.
     *
     * @var string
     */
    protected $path;


    public function __construct(string $path)
    {   
         
        if(!file_exists($path)) {
             throw new \InvalidArgumentException(sprintf('%s does not exist', $path));
        }
        $this->path = $path;
    }

    private function load() :void
    {
        if (!is_readable($this->path)) {
             throw new \RuntimeException(sprintf('%s file is not readable', $this->path));
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {

            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    public function listVars(){
        (new DotEnv(__DIR__ . '/../../.env'))->load();

        // $_ENV es asignado directamente por load() y es confiable incluso cuando
        // putenv()/getenv() no reflejan el cambio (comportamiento conocido en Apache/Windows).
        $get = function(string $key) {
            return $_ENV[$key] ?? (getenv($key) !== false ? getenv($key) : null);
        };

        $vars = array(

            'HOST_CENTRAL'     => $get('HOST_CENTRAL'),
            'HOST_LOCALES'     => $get('HOST_LOCALES'),
            'HOST_APPS'        => $get('HOST_APPS'),
            'DATABASE_CENTRAL' => $get('DATABASE_CENTRAL'),
            'DATABASE_LOCALES' => $get('DATABASE_LOCALES'),
            'DATABASE_TANGOBIS'=> $get('DATABASE_TANGOBIS'),
            'DATABASE_UY'      => $get('DATABASE_UY'),
            'DATABASE_SUC_UY'  => $get('DATABASE_SUC_UY'),
            'DATABASE_APPS'    => $get('DATABASE_APPS'),
            'USER'             => $get('USER'),
            'PASS'             => $get('PASS'),
            'PASS_LOCALES'     => $get('PASS_LOCALES'),
            'CHARACTER'        => $get('CHARACTER'),
            'ENV'              => $get('ENV'),
            'GOCUOTAS_EMAIL'   => $get('GOCUOTAS_EMAIL'),
            'GOCUOTAS_APIKEY'  => $get('GOCUOTAS_APIKEY'),
            'GOCUOTAS_SANDBOX' => $get('GOCUOTAS_SANDBOX'),

        );

        return $vars;


    }

}