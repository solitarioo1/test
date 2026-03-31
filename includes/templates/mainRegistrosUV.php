    <main class="principal-registros">
        <div class="titulo-registros">
            <h1>Registros En Tiempo Real</h1>
            <div class="estado-registro">
                REGISTRANDO ...
            </div>
        </div>
        
        <div class="contenedor-grafica-registros">
            <div>
                <aside class="columna-izquierda">
                    <h2>ESTACIONES</h2>
                    <!-- El buscador y contenedor de scroll se crean automáticamente por JS -->
                    <ul id="lista-estaciones"></ul>
                </aside>
                
                <section class="columna-centro">
                    <div class="contenedor-mapa">
                        <h2>MAPA</h2>
                        <div id="mapa-estaciones" class="mapa-estaciones">
                            <div class="marcador-personalizado">
                                <div class="pulso"></div>
                                <span>Estación 1</span>
                            </div>
                        </div> 
                    </div>
                    <div id="leyenda-uv">
                        <img src="build/img/niveles/legend.svg" alt="Leyenda de UV">
                    </div>
                </section>
            </div>

            <div>
                <aside class="columna-derecha">
                    <div id="grafico-uv">
                        <div>
                            <h2 id="nombre-estacion">Selecciona una estación</h2>
                            <time id="hora-registro"></time>
                            <p id="coordenadas-estacion">Coordenadas: -</p>
                        </div>
                        <div class="grafico-uv-contenedor">
                            <canvas id="graficoUv" width="800" height="400"></canvas>
                        </div>
                    </div>
                
                    <!-- <div id="leyenda-uv">
                        <img src="build/img/niveles/legend.svg" alt="Leyenda de UV">
                    </div> -->
                
                    <div id="valores-numericos">
                        <div class="info-actualizacion">
                            <p>Última actualización: <time id="ultima-actualizacion">-</time></p>
                        </div>
                
                        <div class="grid-valores">
                            <div class="valor temperatura">
                                <p>Temperatura: <span id="valor-temp">-</span>°C</p>
                            </div>
                            <div class="valor humedad">
                                <p>Humedad: <span id="valor-humedad">-</span>%</p>
                            </div>
                            <div class="valor indice-uv">
                                <p>Índice UV: <span id="valor-uv">-</span></p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div> 
        </div>
    </main>