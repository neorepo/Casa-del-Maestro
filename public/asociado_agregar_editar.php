<?php

// configuration
require '../includes/bootstrap.php';

$asociado = [];
$errors = [];
$cantErrs = null;
// Las localidades estarán disponibles solo cuando exista el id de la provincia.
// Son 22793 localidades, solo listaremos las que pertenezcan a la provincia seleccionada
$localidades = [];

$edit = array_key_exists('aid', $_GET);

// Si true
if ($edit) {
    // Recuperamos los datos del asociado de la base de datos
    $asociado = getAsociadoPorId();

    // Recuperamos las localidades por el id de la provincia
    $localidades = getLocalidadesPorIdProvincia( (int) $asociado['id_provincia'] );

} else {
    $asociado = [
        'id_asociado' => null,'apellido' => null,'nombre' => null,'fecha_nacimiento' => null,'tipo_documento' => null,
        'num_documento' => null,'num_cuil' => null,'categoria_ingreso' => null,'email' => null,'telefono_movil' => null,
        'telefono_linea' => null,'domicilio' => null,'id_provincia' => null,'id_localidad' => null,'sexo' => null
    ];
}

/**
 * MÉTODO POST
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Revisar esta condición por que al presionar la tecla control + F5, me saca de la aplicación
    // esto puede ser por que se genera otro token
    if (array_key_exists('token', $_POST)) {
        if (!Token::validate($_POST['token'])) {
            // Si el token CSRF que enviaron no coincide con el que enviamos.
            redirect('/usuario_logout.php');
        }
    }
    // No existe la key token
    else {
        redirect('/usuario_logout.php');
    }

    if (array_key_exists('cancel', $_POST)) {
        if ($asociado['id_asociado'] === null) {
            redirect('/');
        }
        // Re dirigimos al usuario a la vista de detalle.
        redirect('/asociado_detalle.php?aid=' . $asociado['id_asociado']);
    }

    foreach ($asociado as $key => $value) {
        if ( array_key_exists( $key, $_POST ) ) {
            $asociado[$key] = escape( $_POST[$key] );
        }
    }

    // Validaciones, el array de mensajes de error se encuentra en la carpeta includes/bootstrap

    // Apellido
    if ( !$asociado['apellido'] ) {
        $errors['apellido'] = $messages['required'];
    } else if ( !onlyletters( $asociado['apellido'] ) ) {
        $errors['apellido'] = $messages['onlyLetters'];
    } else if ( !minlength( $asociado['apellido'], LONGITUD_MINIMA) ) {
        $errors['apellido'] = $messages['minLength'];
    } else if ( !maxlength($asociado['apellido'], LONGITUD_MAXIMA) ) {
        $errors['apellido'] = $messages['maxLength'];
    }

    // Nombre
    if ( !$asociado['nombre'] ) {
        $errors['nombre'] = $messages['required'];
    } else if ( !onlyletters( $asociado['nombre'] ) ) {
        $errors['nombre'] = $messages['onlyLetters'];
    } else if ( !minlength( $asociado['nombre'], LONGITUD_MINIMA) ) {
        $errors['nombre'] = $messages['minLength'];
    } else if ( !maxlength($asociado['nombre'], LONGITUD_MAXIMA) ) {
        $errors['nombre'] = $messages['maxLength'];
    }

    // Fecha de nacimiento
    if ( !$asociado['fecha_nacimiento'] ) {
        $errors['fecha_nacimiento'] = $messages['required'];
    } else if ( !validate_date( $asociado['fecha_nacimiento'] ) ) {
        $errors['fecha_nacimiento'] = $messages['valid_date'];
    } else if ( !validLegalAge( calculateAge( $asociado['fecha_nacimiento'] ) ) ) {
        $errors['fecha_nacimiento'] = $messages['valid_legal_age'];
    }

    // Tipo de documento
    if ( !$asociado['tipo_documento'] ) {
        $errors['tipo_documento'] = $messages['required'];
    } else if ( !in_array( $asociado['tipo_documento'], ['DNI', 'LC', 'LE'] ) ) {
        $errors['tipo_documento'] = $messages['valid_document_type'];
    }

    // Número de documento
    $valid1 = false;
    if ( !$asociado['num_documento'] ) {
        $errors['num_documento'] = $messages['required'];
    } else if ( preg_match('/^[\d]{8}$/', $asociado['num_documento']) ) {
        
        if ( isset( $asociado['id_asociado'] ) ) {
            $rows = existeNumDeDocumentoAsociado( $asociado['num_documento'], $asociado['id_asociado'] );
        } else {
            $rows = existeNumDeDocumentoAsociado( $asociado['num_documento'] );
        }

        // Unique
        if (count($rows) == 1) {
            $errors['num_documento'] = str_replace(':f', 'número de documento', $messages['unique'] );
        } else {
            $valid1 = true;
        }
    } else {
        $errors['num_documento'] = $messages['valid_document'];
    }

    // Número de cuil
    $valid2 = false;
    if ( !$asociado['num_cuil'] ) {
        $errors['num_cuil'] = $messages['required'];
    } else if ( validar_cuit( $asociado['num_cuil'] ) ) {

        if ( isset( $asociado['id_asociado'] ) ) {
            $rows = existeNumDeCuilAsociado( $asociado['num_cuil'], $asociado['id_asociado'] );
        } else {
            $rows = existeNumDeCuilAsociado( $asociado['num_cuil'] );
        }
        
        // Unique
        if (count($rows) == 1) {
            $errors['num_cuil'] = str_replace(':f', 'número de cuil', $messages['unique'] );
        } else {
            $valid2 = true;
        }
    } else {
        $errors['num_cuil'] = $messages['valid_cuil'];
    }

    if($valid1 && $valid2) {
        $temp = substr( $asociado['num_cuil'], 2, -1 );
        if( strcmp($temp, $asociado['num_documento']) !== 0 ) {
            $errors['num_cuil'] = 'El número de cuil no se corresponde con el documento ingresado anteriormente.';
        }
    }

    // Condición de ingreso
    if ( !$asociado['categoria_ingreso'] ) {
        $errors['categoria_ingreso'] = $messages['required'];
    } else if ( !in_array( $asociado['categoria_ingreso'], ['ACTIVO', 'ADHERENTE', 'JUBILADO'] ) ) {
        $errors['categoria_ingreso'] = $messages['valid_entry_condition'];
    }

    // E-mail, no es un campo requerido
    if ( !$asociado['email'] ) {
        // $errors['email'] = $messages['required'];

        // Si no tengo el email, no puedo insertar un string vacío por que el campo es unique. Leer README.txt
        $asociado['email'] = null;
        
    } else if ( valid_email( $asociado['email'] ) ) {
        
        if ( isset( $asociado['id_asociado'] ) ) {
            $rows = existeEmailAsociado( $asociado['email'], $asociado['id_asociado'] );
        } else {
            $rows = existeEmailAsociado( $asociado['email'] );
        }
        
        // Unique
        if (count($rows) == 1) {
            $errors['email'] = str_replace(':f', 'correo electrónico', $messages['unique'] );
        }
    } else {
        $errors['email'] = $messages['valid_email'];
    }

    // Teléfono móvil
    if ( !$asociado['telefono_movil'] ) {
        $errors['telefono_movil'] = $messages['required'];
    } else if ( validar_tel( $asociado['telefono_movil'] ) ) {
        if( isset( $asociado['id_asociado'] ) ) {
            $rows = existeTelefonoMovilAsociado( $asociado['telefono_movil'], $asociado['id_asociado'] );
        } else {
            $rows = existeTelefonoMovilAsociado( $asociado['telefono_movil'] );
        }
        
        // Unique
        if (count($rows) == 1) {
            $errors['telefono_movil'] = str_replace(':f', 'teléfono móvil', $messages['unique'] );
        }
    } else {
        $errors['telefono_movil'] = $messages['valid_mobile_phone'];
    }

    // Teléfono de línea, no es un campo requerido
    if ( !$asociado['telefono_linea'] ) {
        // $errors['telefono_linea'] = $messages['required'];

        // Si no tengo el telefono de línea, puedo insertar un string vácio por que el campo no es unique
        // pero, para mantener la consistencia de los datos, se insertará el valor por defecto null
        $asociado['telefono_linea'] = null;

    }
    // Si tenemos el teléfono de línea lo validamos
    else if ( !validar_tel( $asociado['telefono_linea'] ) ) {
        $errors['telefono_linea'] = $messages['valid_phone'];
    }

    // Domicilio
    if ( !$asociado['domicilio'] ) {
        $errors['domicilio'] = $messages['required'];
    }

    // Provincia
    if ( !$asociado['id_provincia'] ) {
        $errors['id_provincia'] = $messages['required'];
    } else if( isValidProvinceId( $asociado['id_provincia'] ) ) {

        // Cargamos las localidades despues de que tenemos el id de la provincia
        $localidades = getLocalidadesPorIdProvincia( (int) $asociado['id_provincia'] );
        
    } else {
        $errors['id_provincia'] = "Seleccione una provincia de la lista.";
    }

    // Localidad, aquí se verifica que la localidad pertenezca a la provincia selecionada
    if ( !$asociado['id_localidad'] ) {
        $errors['id_localidad'] = $messages['required'];
    } else if( isPositiveInt( $asociado['id_localidad'] ) )  {
        // Si el id de la provincia es vacío, devolvera 0 filas
        $rows = existeLocalidadDeProvincia( (int) $asociado['id_localidad'], (int) $asociado['id_provincia'] );
        
        if (count($rows) == 0) {
            $errors['id_localidad'] = 'Seleccione una localidad de la lista.';
        }
    } else {
        $errors['id_localidad'] = 'Seleccione una localidad de la lista.';
    }
    
    // Sexo
    if ( !$asociado['sexo'] ) {
        $errors['sexo'] = $messages['required'];
    } else if( !in_array( $asociado['sexo'], ['F', 'M'] ) ) {
        $errors['sexo'] = $messages['valid_sex'];
    }

    /**
     * Si no existen errores en el array
     */
    if( empty($errors) ) {
        // Recibimos los datos del asociado despues de la inserción o actualización.
        $asociado = save( $asociado );
        // Verificamos que el valor de retorno sea distinto de false.
        if ( $asociado ) {
            // Seteamos el mensaje flash para la vista
            Flash::addFlash('Los datos fueron guardados correctamente.', 'success');
            // Re dirigimos al usuario a la vista de detalle.
            redirect('/asociado_detalle.php?aid=' . $asociado['id_asociado']);
        } else {
            Flash::addFlash('Lo sentimos, no pudimos guardar el registro.', 'danger');
            redirect('/');
        }
    }
    else {
        $n = count($errors);
        $cantErrs = 'Tienes ' . $n . ' error';
        $cantErrs .= $n == 1 ? '.' : 'es.';
        $cantErrs .= ' Por favor, revise el formulario.';
    }
}

$title = $edit ? 'Editar asociado' : 'Registrar asociado';

render('asociado/agregar-editar.html', [
    'title' => $title,
    'asociado' => $asociado,
    'errors' => $errors,
    'cantErrs' => $cantErrs,
    'localidades' => $localidades,
    'edit' => $edit
]);

// Conmuta en función de si existe el id del asociado
function save($asociado) {
    if ( $asociado['id_asociado'] === null ) {
        return insertarAsociado($asociado);
    }
    return actualizarAsociado($asociado);
}

// Devuelve un array de datos de asociado, de lo contrario devuelve false
function actualizarAsociado($asociado) {
    $now = date('Y-m-d H:i:s');
    $asociado['fecha_nacimiento'] = dateToDb($asociado['fecha_nacimiento']);
    try {
        $db = Db::getInstance();
        // begin the transaction
        $db->beginTransaction();

        // Consulta 1
        $sql = 'UPDATE asociado set apellido = ?, nombre = ?, sexo = ?, fecha_nacimiento = ?, tipo_documento = ?, num_documento = ?, 
        num_cuil = ?, categoria_ingreso = ?, email = ?, domicilio = ?, id_localidad = ?, last_modified = ? WHERE id_asociado = ? ; ';
    
        Db::query($sql, capitalize($asociado['apellido']), capitalize($asociado['nombre']), $asociado['sexo'], $asociado['fecha_nacimiento'], 
        $asociado['tipo_documento'], $asociado['num_documento'], $asociado['num_cuil'], $asociado['categoria_ingreso'], $asociado['email'], 
        $asociado['domicilio'], $asociado['id_localidad'], $now, $asociado['id_asociado']);

        // Consulta 2
        $sql = 'UPDATE telefono set telefono_movil = ?, telefono_linea = ? WHERE id_asociado = ? ; ';
    
        Db::query($sql, $asociado['telefono_movil'], $asociado['telefono_linea'], $asociado['id_asociado']);

        // commit the transaction
        $db->commit();
    } catch (PDOException $e) {
        // roll back the transaction if something failed
        $db->rollback();
        // trigger_error('Error:' . $e->getMessage(), E_USER_ERROR);
        return false;
    }
    return $asociado;
}

// Devuelve un array de datos de asociado, de lo contrario devuelve false
function insertarAsociado($asociado) {
    $now = date('Y-m-d H:i:s');
    $asociado['fecha_nacimiento'] = dateToDb($asociado['fecha_nacimiento']);
    try {
        $db = Db::getInstance();
        // begin the transaction
        $db->beginTransaction();

        // Consulta 1
        $sql = 'INSERT INTO asociado (apellido, nombre, sexo, fecha_nacimiento, tipo_documento, num_documento, num_cuil, categoria_ingreso, 
        email, domicilio, id_localidad, created, last_modified) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        // Run consulta 1
        Db::query($sql, capitalize($asociado['apellido']), capitalize($asociado['nombre']), $asociado['sexo'], $asociado['fecha_nacimiento'], 
        $asociado['tipo_documento'], $asociado['num_documento'], $asociado['num_cuil'], $asociado['categoria_ingreso'], $asociado['email'], 
        $asociado['domicilio'], $asociado['id_localidad'], $now, $now);

        // Seteamos el id del nuevo asociado insertado en la base de datos, para re dirigir a la página de detalle
        $asociado['id_asociado'] = Db::getInstance()->lastInsertId();// ( lastInsertId() devuelve un tipo string ).

        // Consulta 2
        $sql = 'INSERT INTO telefono (telefono_movil, telefono_linea, id_asociado) VALUES(?, ?, ?)';

        // Run consulta 2
        Db::query($sql, $asociado['telefono_movil'], $asociado['telefono_linea'], (int) $asociado['id_asociado']);
    
        // commit the transaction
        $db->commit();
    } catch (PDOException $e) {
        // roll back the transaction if something failed
        $db->rollback();
        // trigger_error('Error:' . $e->getMessage(), E_USER_ERROR);
        return false; // Deberíamos devolver -1 en caso de error si estuvieramos en JAVA :)
    }
    return $asociado;
}