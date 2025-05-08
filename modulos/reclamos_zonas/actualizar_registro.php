// Query actualizado
    $query = "UPDATE reclamos_zonas 
              SET cantidad_reclamos = :cantidad 
              WHERE zona = :zona AND mes = :mes AND anio = :anio";