    <section class="mapas contenedor">
        <h2><span>Mapas</span> de calor</h2>
        <div class="descripcion-mapas">
            <p>Visualiza la distribución geográfica de temperatura, humedad e índice UV mediante mapas de calor actualizados en tiempo real.</p>
        </div>

        <div class="riel-mapas">
            <div class="controles-riel">
                <button class="control-riel anterior" aria-label="Ver mapa anterior">
                    <img src="/build/img/iconos/izquierda.svg" alt="Anterior">
                </button>
                <button class="control-riel siguiente" aria-label="Ver mapa siguiente">
                    <img src="/build/img/iconos/derecha.svg" alt="Siguiente">
                </button>
            </div>

            <div class="contenedor-riel">
                <div class="mapa-calor" id="mapa-temperatura">
                    <h3>Mapa de Temperatura</h3>
                    <div class="visualizacion-mapa">
                        <div id="leaflet-temperatura" class="placeholder-mapa"></div>
                    </div>
                    <div class="leyenda-mapa">
                        <div class="escala-colores">
                            <div class="color-min">10°C</div>
                            <div class="gradiente temperatura-gradiente"></div>
                            <div class="color-max">40°C</div>
                        </div>
                    </div>
                </div>

                <div class="mapa-calor" id="mapa-humedad">
                    <h3>Mapa de Humedad</h3>
                    <div class="visualizacion-mapa">
                        <div id="leaflet-humedad" class="placeholder-mapa"></div>
                    </div>
                    <div class="leyenda-mapa">
                        <div class="escala-colores">
                            <div class="color-min">0%</div>
                            <div class="gradiente humedad-gradiente"></div>
                            <div class="color-max">100%</div>
                        </div>
                    </div>
                </div>

                <div class="mapa-calor" id="mapa-uv">
                    <h3>Mapa de Índice UV</h3>
                    <div class="visualizacion-mapa">
                        <div id="leaflet-uv" class="placeholder-mapa"></div>
                    </div>
                    <div class="leyenda-mapa">
                        <div class="escala-colores">
                            <div class="color-min">0</div>
                            <div class="gradiente uv-gradiente"></div>
                            <div class="color-max">11+</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="indicadores-riel">
            <button class="indicador activo" data-mapa="temperatura" aria-label="Ver mapa de temperatura"></button>
            <button class="indicador" data-mapa="humedad" aria-label="Ver mapa de humedad"></button>
            <button class="indicador" data-mapa="uv" aria-label="Ver mapa de índice UV"></button>
        </div>

        <div class="info-actualizacion-mapas">
            <p>Última actualización de mapas: <time id="ultima-actualizacion-mapas">-</time></p>
        </div>
    </section>