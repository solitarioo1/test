<body> 
    <header class=" header header-registros">
        <div class="contenido-header "> 
            <div class="barra contenedor">

                <div class="logo">      
                    <img src="/build/img/logo/LOGO_BLANCO.webp" alt="logo-inti" >             

                </div>

                <nav class="nav-principal">
                    <a href="/index">Inicio</a>
                    <a href="/nosotros">Nosotros</a>
                    <a href="/productos">Productos</a>
                    <a class="activo" href="/registros">Registros</a>
                    <a href="/contacto">Contactos</a>
                </nav>

            </div>

            <div class = "texto-header">
                <h1> Datos en vivo sobre la radiación UV para protegerte con información precisa. </h1>
            </div>

        </div>    
    </header>


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
                            <!-- <canvas id="graficoUv" width="800" height="400"></canvas> -->
                            <div id="graficoUv" style="width: 100%; height: 400px;"></div>
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



    <section class="sobre-radiacion ">
        <h2><span>Conoce </span> mas sobre la radiación</h2>
        <div class="radiacion-contenido">
          <div class="resumen-rad">
            <p>
              "En Perú, la radiación solar alcanza niveles extremos por su cercanía al ecuador y la altitud andina, elevando el riesgo de daños cutáneos y oculares. Para protegerse, se debe usar bloqueador SPF 50+, reaplicado cada dos horas, junto con gorros de ala ancha, lentes UV y ropa protectora. Evitar la exposición entre 10 a.m. y 4 p.m., buscar sombra e hidratarse son clave. Estas medidas, respaldadas por campañas estatales y prácticas ancestrales, como los sombreros andinos, son vitales para la salud pública (MINSA, 2023; SENAMHI, 2023)".
            </p>
            <div class="radiacion-header-cta">
              <a href="/blogRadiacion" class="btn-conocenos">Conoce más sobre radiación</a>
            </div>
          </div>
      
          <div class="radiacion-imagen">
            <img src="build/img/lugares_instalacion/altaTemperatura.webp" alt="Protección contra radiación">
          </div>
        </div>
    </section>
          
    <section class="problemas-frecuentes ">
        <h1 class="titulo-principal">¿POR QUÉ DEBEMOS CUIDARNOS DE LA RADIACIÓN?</h1>

        <div class="contenedor-problemas">
            <!-- Columna izquierda - Imágenes -->
            <div class="columna-imagen izquierda">
                <div class="caja-imagen">
                    <img src="build/img/salud/quemadura_piel.webp" alt="Radiación imagen 1" class="imagen-animada">
                </div>
                <div class="caja-imagen">
                    <img src="build/img/salud/playa.webp" alt="Radiación imagen 2" class="imagen-animada">
                </div>
                <div class="caja-imagen">
                    <img src="build/img/lugares_instalacion/familia.webp" alt="Radiación imagen 3" class="imagen-animada">
                </div>
            </div>

            <!-- Columna central - Preguntas y respuestas -->
            <div class="columna-contenido">
                <div class="caja-pregunta">
                    <h2>PROBLEMAS FRECUENTES EN LA PIEL</h2>
                    <p class="respuesta">
                        Estudios de la <strong>Revista Peruana de Medicina Experimental y Salud Pública</strong> confirman que 
                        la exposición prolongada causa fotoenvejecimiento, quemaduras solares y cáncer de piel. La 
                        <strong>Universidad Técnica de Machala</strong> destaca que la radiación UV afecta no solo la piel, 
                        sino también los ojos y el sistema inmunológico, favoreciendo enfermedades cutáneas.
                    </p>
                </div>

                <div class="caja-pregunta">
                    <h2>CUÁNTAS PERSONAS MUEREN POR CÁNCER DE PIEL AL AÑO</h2>
                    <p class="respuesta">
                        Según la <strong>American Cancer Society</strong>, miles de personas fallecen cada año debido a la 
                        radiación solar, especialmente por melanoma. En el caso de Perú, la <strong>Revista Peruana de 
                        Medicina Experimental y Salud Pública</strong> señala que el cáncer de piel ha aumentado en zonas 
                        de alta radiación UV.
                    </p>
                </div>

                <div class="caja-pregunta">
                    <h2>QUIÉNES SON LOS MÁS PERJUDICADOS</h2>
                    <p class="respuesta">
                        De acuerdo con la <strong>Universidad Técnica de Machala</strong>, los niños y trabajadores al aire 
                        libre tienen mayor riesgo de desarrollar enfermedades cutáneas. La 
                        <strong>American Cancer Society</strong> menciona que las personas con piel clara o antecedentes 
                        familiares de cáncer de piel son más propensas a los efectos nocivos de la radiación UV.
                    </p>
                </div>

                <div class="caja-pregunta">
                    <h2>CÓMO CUIDARNOS DE LA RADIACIÓN SOLAR</h2>
                    <p class="respuesta">
                        Investigaciones de la <strong>Universidad de Valladolid</strong> recomiendan el uso de protector solar, 
                        ropa adecuada y evitar la exposición entre las 10 a.m. y 4 p.m. Además, la 
                        <strong>Revista Peruana de Medicina Experimental y Salud Pública</strong> sugiere programas de 
                        concienciación para reducir el impacto de la radiación UV en la población.
                    </p>
                </div>
            </div>

            <!-- Columna derecha - Imágenes -->
            <div class="columna-imagen derecha">
                <div class="caja-imagen">
                    <img src="build/img/salud/perdida_vista.webp" alt="Radiación imagen 4" class="imagen-animada">
                </div>
                <div class="caja-imagen">
                    <img src="build/img/lugares_instalacion/estudiantes.webp" alt="Radiación imagen 5" class="imagen-animada">
                </div>
                <div class="caja-imagen">
                    <img src="build/img/lugares_instalacion/universitario.webp" alt="Radiación imagen 6" class="imagen-animada">
                </div>
            </div>
        </div>
    </section>




