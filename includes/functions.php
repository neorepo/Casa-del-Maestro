﻿<?php

require_once("constants.php");


/**
 * PERSISTENCIA
 * FUNCIONES PARA MANTENER LA INTEGRIDAD DE LOS CAMPOS UNIQUE EN LA BASE DE DATOS
 */
function existeNumDeDocumentoAsociado($num_documento, $id_asociado = null) {
    if ($id_asociado) {
        $q = 'SELECT num_documento FROM asociado WHERE num_documento = ? AND id_asociado != ? LIMIT 1 ;';
        return Db::query($q, $num_documento, $id_asociado);
    } else {
        $q = 'SELECT num_documento FROM asociado WHERE num_documento = ? LIMIT 1 ;';
        return Db::query($q, $num_documento);
    }
}

function existeNumDeCuilAsociado($num_cuil, $id_asociado = null) {
    if ($id_asociado) {
        $q = 'SELECT num_cuil FROM asociado WHERE num_cuil = ? AND id_asociado != ? LIMIT 1 ;';
        return Db::query($q, $num_cuil, $id_asociado);
    } else {
        $q = 'SELECT num_cuil FROM asociado WHERE num_cuil = ? LIMIT 1 ;';
        return Db::query($q, $num_cuil);
    }
}

function existeEmailAsociado($email, $id_asociado = null) {
    $email = strtolower($email);
    if ($id_asociado) {
        $q = 'SELECT email FROM asociado WHERE email = ? AND id_asociado != ? LIMIT 1 ;';
        return Db::query($q, $email, $id_asociado);
    } else {
        $q = 'SELECT email FROM asociado WHERE email = ? LIMIT 1;';
        return Db::query($q, $email);
    }
}

function existeTelefonoMovilAsociado($telefono_movil, $id_asociado = null) {
    if ($id_asociado) {
        $q = 'SELECT telefono_movil FROM telefono WHERE telefono_movil = ? AND id_asociado != ? LIMIT 1;';
        return Db::query($q, $telefono_movil, $id_asociado);
    } else {
        $q = 'SELECT telefono_movil FROM telefono WHERE telefono_movil = ? LIMIT 1;';
        return Db::query($q, $telefono_movil);
    }
}

function existeLocalidadDeProvincia($id_localidad, $id_provincia) {
    $q = 'SELECT id_localidad FROM localidad WHERE id_localidad = ? AND id_provincia = ?';
    return Db::query($q, $id_localidad, $id_provincia);
}

function findById($id_asociado) {
    $q = 'SELECT
          a.id_asociado, a.apellido, a.nombre,
          a.sexo, strftime("%d/%m/%Y",a.fecha_nacimiento) AS fecha_nacimiento, a.tipo_documento,
          a.num_documento, a.num_cuil, a.domicilio,
          a.categoria_ingreso, a.email, strftime("%d/%m/%Y %H:%M",a.created) AS created, a.last_modified,
          t.telefono_movil, t.telefono_linea,
          p.id_provincia, p.nombre AS provincia,
          l.id_localidad, l.nombre AS localidad, l.cp
          FROM asociado a INNER JOIN telefono t ON a.id_asociado = t.id_asociado INNER JOIN localidad l ON a.id_localidad = l.id_localidad 
          INNER JOIN provincia p ON l.id_provincia = p.id_provincia WHERE a.deleted = 0 AND a.id_asociado = ? LIMIT 1;';

    return Db::query($q, $id_asociado);
     // Devuelve un objeto de transferencia si lo encuentra,
     // devuelve nulo (null) en caso de error o si no se encuentra
}

function getUrlParam($name) {
    if (!array_key_exists($name, $_GET)) {
        throw new Exception('URL parameter "' . $name . '" not found.');
    }
    return $_GET[$name];
}

function getAsociadoPorId() {

    $id = null;

    try {
        $id = getUrlParam('aid');
    } catch (Exception $ex) {
        render('error/404.html', ['title' => 'Error', 'message' => 'no se proporcionó ningún identificador de asociado.']);
    }
    if ( !is_numeric($id) ) {
        render('error/404.html', ['title' => 'Error', 'message' => 'se proporcionó un identificador de asociado no válido.']);
    }
    
    $rows = findById($id);
    
    if ( !$rows ) {
        render('error/404.html', ['title' => 'Error', 'message' => 'se proporcionó un identificador de asociado desconocido.']);
    }

    return $rows[0]; // Siempre retornará un asociado
}

function eliminarAsociado($id_asociado) {
    $last_modified = date('Y-m-d H:i:s');
    /**
     * Habilitamos la eliminación ON DELETE CASCADE
     * para base de datos SQLite, siempre que la sentencia sql sea DELETE
     * Db::getInstance()->exec('PRAGMA foreign_keys = ON ;');
     */
    try {
        $db = Db::getInstance();
        // begin the transaction
        $db->beginTransaction();

        $q = 'UPDATE asociado SET last_modified = ?, deleted = ? WHERE id_asociado = ?;';
        Db::query($q, $last_modified, true, $id_asociado);

        // commit the transaction
        $db->commit();
    } catch (PDOException $e) {
        // roll back the transaction if something failed
        $db->rollback();
        trigger_error('Error:' . $e->getMessage(), E_USER_ERROR);
        return false; // Deberíamos devolver -1 en caso de error si estuvieramos en JAVA
    }
    return true;
    // Devuelve verdadero en caso de éxito, falso en caso de error
}

/**
 * Listar asociados, listamos también el campo teléfono móvil para buscar en el caso de que se intente ingresar 2 veces el
 * número móvil y verificar a quién le pertenece
 */
function listarAsociados() {
    $q = 'SELECT a.id_asociado, a.apellido, a.nombre, a.tipo_documento, a.num_documento, a.num_cuil, t.telefono_movil, a.categoria_ingreso
          FROM asociado a INNER JOIN telefono t ON a.id_asociado = t.id_asociado WHERE a.deleted = 0 ORDER BY a.apellido, a.categoria_ingreso;';
    return Db::query($q);
}

function getLocalidadesPorIdProvincia($id_provincia) {
    $q = 'SELECT * FROM localidad WHERE id_provincia = ? ORDER BY nombre ;';
    return Db::query($q, $id_provincia);
}

/**
 * Usuario
 */
function existeNumDeDocumentoUsuario($num_documento, $id_usuario = null) {
    if ($id_usuario) {
        $q = 'SELECT num_documento FROM usuario WHERE num_documento = ? LIMIT 1 ;';
        return Db::query($q, $num_documento);
    } else {
        $q = 'SELECT num_documento FROM usuario WHERE num_documento = ? AND id_usuario != ? LIMIT 1 ;';
        return Db::query($q, $num_documento, $id_usuario);
    }
}

function existeEmailUsuario($email, $id_usuario = null) {
    $email = strtolower($email);
    if ($id_usuario) {
        $q = 'SELECT id_usuario FROM usuario WHERE email = ? AND id_usuario != ? LIMIT 1 ;';
        return Db::query($q, $email, $id_usuario);
    } else {
        $q = 'SELECT id_usuario FROM usuario WHERE email = ? LIMIT 1 ;';
        return Db::query($q, $email);
    }
}

/**
 * *******************************************************************************
 * FUNCIONES ÚTILES
 */

/**
 * Válida si es un id válido para la provincia
 */
function isValidProvinceId($id) {
    if ( get_int($id) ) {
        $id = (int) $id;
        // Si es un número entero entre 1-24 inclusivo. Los ids de las 24 provincias en la tabla provincia de la base de datos
        if ($id > 0 && $id < 25) {
            return true;
        }
    }
    return false;
}

/**
 * Devuelve true si el string contiene un caracter numérico positivo
 * false en caso contrario
 */
function isPositiveInt($n) {
    if( get_int($n) ) {
        $n = (int) $n;
        if($n > 0) {
            return true;
        }
    }
    return false;
}

/**
 * Válida un string con caracteres numéricos enteros
 */
function get_int($n) {
    if ($n === null) {
        return 0;
    }
    // Si es un carácter numérico entero
    return preg_match('/^[+-]?\d+$/', $n);
}

/**
 * Válida un string con caracteres numéricos decimales
 */
function get_float($n) {
    if ($n === null) {
        return 0;
    }
    // Si es un caracter numérico decimal
    return preg_match('/^[+-]?\d*(?:\.\d*)?$/', $n);
}

/**
 * Devuelve la fecha según el formato especificado.
 */
function get_date($format = '%A, %#d de %B de %Y') {/*'%Y-%m-%d %H:%M:%S'*/
    $month = [
        1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 
        'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
    ];

    $day = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    
    /**
     * %A 	A full textual representation of the day =>	Sunday through Saturday
     * %e = %#d	Day of the month, with a space preceding single digits. Not implemented as described on Windows.
     * See below for more information. 	1 to 31
     * %B 	Full month name, based on the locale =>	January through December
     * %Y 	Four digit representation for the year 	Example: 2038
     */
    
    $timestamp = time();
    
    /**
     * %w 	Numeric representation of the day of the week 	0 (for Sunday) through 6 (for Saturday)
     */
    $date = preg_replace('@%[aA]@', $day[ (int) strftime( '%w', $timestamp ) ], $format);
    /**
     * %m 	Two digit representation of the month 	01 (for January) through 12 (for December)
     */
    $date = preg_replace('@%[bB]@', $month[ (int) strftime( '%m', $timestamp ) ], $date);
    
    $date = strftime($date, $timestamp);

    return $date;
}

/**
 * formato: día-mes-año o año-mes-día
 * Ejemplos:
 * echo formatDateEs('NOW'); => sábado, 7 de noviembre de 2020
 * echo formatDateEs('23-04-1994'); => sábado, 23 de abril de 1994
 * echo formatDateEs('1995-03-06'); => lunes, 6 de marzo de 1995
 * retorna la fecha en el formato día semana, día de mes, de año
 */
function formatDateEs($date) {
    $months = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    
    $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    
    $dt = new DateTime($date);

    $idxDay = $dt->format("w");
    $idxMonth = $dt->format("n");

    return $days[$idxDay] . ", " . $dt->format("j") . " de " . $months[$idxMonth] . " de " . $dt->format("Y");
}

/**
 * Convierte la fecha del formato de base de datos año-mes-día (2000-03-06)
 * al formato día-mes-año (06/03/2000)
 */
function dateToTemplate($date) {
    $date = explode('-', $date);
    return  $date[2] . '/' . $date[1] . '/' . $date[0]; 
}

/**
 * Convierte la fecha del formato día-mes-año (06/03/2000)
 * al formato de base de datos año-mes-día (2000-03-06)
 */
function dateToDb($date) {
    $date = explode('/', $date);
    return  $date[2] . '-' . $date[1] .'-' . $date[0]; 
}

function formatDateTime($datetime) {
    $dt = new DateTime($datetime);
    return $dt->format('j/n/Y H:i');// $date->format('j/n/Y') => 6/8/2020
}

/**
 * Escapa caracteres no permitidos
 */
function escape($value) {
    return htmlspecialchars( stripslashes( trim($value) ) );
}

/**
 * Convierte a mayúsculas el primer caracter de cada palabra
 */
function capitalize($string) {
    return ucwords( mb_strtolower($string, 'UTF-8') );
}

/**
 * Válida la longitud de un string
 */
function check_length($string, $minlength, $maxlength) {
    $strlen = mb_strlen($string);
    if ($strlen >= $minlength && $strlen <= $maxlength) {
        return true;
    }
    return false;
}

/**
 * Validación de fecha en el formato 03/02/2019 o 3/2/2019
 */
function validate_date($date) {
    $matches = [];
    $pattern = '/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/';
    if (!preg_match($pattern, $date, $matches)) return false;

    if( !($matches[3] >= 1905 && $matches[3] <= date('Y') ) ) {
        return false;
    }

    //checkdate ( int $month , int $day , int $year ) : bool checkdate(12, 31, 2000)
    if (!checkdate($matches[2], $matches[1], $matches[3])) return false;
    // return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
    return true;
}

/**
 * https://dev.mysql.com/doc/mysql-tutorial-excerpt/8.0/en/date-calculations.html
 * TIMESTAMPDIFF(YEAR,'2033-03-03',CURDATE())
 * 
 * Obtener la edad, recibe un string en el formato 23/04/1994
 */
function calculateAge($birth_date) {
    $current_date = date_create( date("Y-m-d") );
    $birth_date = date_create( str_replace('/', '-', $birth_date) );
    $diff = date_diff( $birth_date, $current_date );
    // La fecha de nacimiento debe ser anterior a la fecha actual.
    if($birth_date > $current_date) {
        return $diff->format('%y') * -1;
    }
    return $diff->format('%y');
}

function validLegalAge($age) {
    return ( (int) $age >= 18 );
}
/**
 * L
 */
function leapYear($year) {
    // divisible by 4
    $isLeapYear = ($year % 4 == 0);
    // divisible by 4 and not 100
    $isLeapYear = $isLeapYear && ($year % 100 != 0);
    // divisible by 4 and not 100 unless divisible by 400
    return $isLeapYear || ($year % 400 == 0);
}

/**
 * https://es.stackoverflow.com/questions/136325/validar-tel%C3%A9fonos-de-argentina-con-una-expresi%C3%B3n-regular
 */
function validar_tel($tel) {
    /**
     * sin espacios, puntos u otros símbolos
     */
    if (!preg_match('/^\d+$/', $tel)) {
        return false;
    }
    $re = '/^(?:((?P<p1>(?:\( ?)?+)(?:\+|00)?(54)(?<p2>(?: ?\))?+)(?P<sep>(?:[-.]| (?:[-.] )?)?+)(?:(?&p1)(9)(?&p2)(?&sep))?|(?&p1)(0)(?&p2)(?&sep))?+(?&p1)(11|([23]\d{2}(\d)??|(?(-10)(?(-5)(?!)|[68]\d{2})|(?!))))(?&p2)(?&sep)(?(-5)|(?&p1)(15)(?&p2)(?&sep))?(?:([3-6])(?&sep)|([12789]))(\d(?(-5)|\d(?(-6)|\d)))(?&sep)(\d{4})|(1\d{2}|911))$/D';
    if (preg_match($re, $tel, $match)) {
        return true;
    }
    return false;
}

/**
 * https://es.wikipedia.org/wiki/Clave_%C3%9Anica_de_Identificaci%C3%B3n_Tributaria
 */
function validar_cuit($cuit) { // 27-27369830-2
    if (!preg_match('/^\d{11}$/', $cuit)) {
        return false;
    }
    /**
     * ^ niega la clase, pero sólo si se trata del primer carácter
     * reemplaza todos los caracteres que no son digitos (-, ., ' ').
     * $card_number = '7896-541-230'; $card_number = preg_replace('/\D+/', '', $card_number);
     */
    $cuit = preg_replace('/[^\d]/', '', (string) $cuit);
    $cuit_tipos = [20, 23, 24, 27, 30, 33, 34];

    if (mb_strlen($cuit) != 11) {
        return false;
    }

    $tipo = (int) substr($cuit, 0, 2);

    if (!in_array($tipo, $cuit_tipos, true)) {
        return false;
    }

    $acumulado = 0;
    $digitos = str_split($cuit); // Convertir en un array
    $digito = array_pop($digitos); // Extraer último elemento del array
    $contador = count($digitos);

    for ($i = 0; $i < $contador; $i++) {
        $acumulado += $digitos[9 - $i] * (2 + ($i % 6));
    }

    $verif = 11 - ($acumulado % 11);

    // Si el resultado es 11, el dígito verificador será 0
    // Si no, será el dígito verificador
    $verif = $verif == 11 ? 0 : $verif;

    return $digito == $verif;
}

/**
* Redirects user to destination, which can be
* a URL or a relative path on the local host.
*
* Because this function outputs an HTTP header, it
* must be called before caller outputs any HTML.
*/
function redirect($destination) {
    // handle URL
    if (preg_match("/^https?:\/\//", $destination))
    {
        header("Location: " . $destination);
    }

    // handle absolute path (/login.php and /)
    else if (preg_match("/^\//", $destination))
    {
        $protocol = (isset($_SERVER["HTTPS"])) ? "https" : "http";
        $host = $_SERVER["HTTP_HOST"];
        header("Location: $protocol://$host$destination");
    }

    // handle relative path (login.php)
    else
    {
        // adapted from http://www.php.net/header
        $protocol = (isset($_SERVER["HTTPS"])) ? "https" : "http";
        $host = $_SERVER["HTTP_HOST"];
        $path = rtrim(dirname($_SERVER["PHP_SELF"]), "/\\");
        header("Location: $protocol://$host$path/$destination");
    }

    // exit immediately since we're redirecting anyway
    exit;
}

/**
* Logs out current user, if any.  Based on Example #1 at
* http://us.php.net/manual/en/function.session-destroy.php.
*/
function logout() {
    // unset any session variables
    $_SESSION = array();

    // expire cookie
    if (!empty($_COOKIE[session_name()]))
    {
        setcookie(session_name(), "", time() - 42000);
    }

    // destroy session
    session_destroy();
}

/**
* Renders template, passing in values.
*/
function render($template, $values = []) {
    // if template exists, render it
    if (file_exists("../templates/$template")) {
        // extract variables into local scope
        extract($values);

        $flashes = null;
        if (Flash::hasFlashes()) {
            $flashes = Flash::getFlashes();
        }

        // render header
        require("../templates/inc/header.html");

        // render template
        require("../templates/$template");

        // render footer
        require("../templates/inc/footer.html");

        exit;
    }

    // else err
    else
    {
        trigger_error("Invalid template: $template", E_USER_ERROR);
    }
}

// función para encriptar contraseña
function hashPassword($password) {
    return password_hash($password . PEPPER, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $passwordHash) {
    return (password_verify($password . PEPPER, $passwordHash) == $passwordHash);
}

function onlyletters($value) {
    return preg_match('/^[A-Za-záéíóúÁÉÍÓÚÑñÜü\s]+$/', $value);
}

function onlynumbers($value) {
    return preg_match('/^[\d]+$/', $value);
}

function minlength($value, $minlength) {
    $strlen = mb_strlen(trim($value));
    return ($strlen >= $minlength);
}

function maxlength($value, $maxlength) {
    $strlen = mb_strlen(trim($value));
    return ($strlen <= $maxlength);
}

function valid_email($email) {
    // https://owasp.org/www-community/OWASP_Validation_Regex_Repository
    // $regexEmail = '/^[a-zA-Z0-9_+&*-]+(?:\.[a-zA-Z0-9_+&*-]+)*@(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,7}$/';
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return preg_match('/^[\w.-]+@[\w.-]+\.[A-Za-z]{2,6}$/', $email);
}

function isEmpty($value) {
    return ( !strlen( $value ) );
}

function cuilFormat($cuil) {
    $arr = str_split($cuil);
    $arr[1] .= '-';
    $arr[9] .= '-';
    return implode($arr);
}
