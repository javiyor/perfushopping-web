# PERFUSHOPPING — PLAN MAESTRO DE OPTIMIZACIÓN WEB, CONTENIDO Y CONVERSIÓN

## Objetivo general

Transformar Perfushopping desde un ecommerce centrado principalmente en catálogo hacia una plataforma comercial que combine:

- ecommerce
- contenido
- asesoramiento
- recomendadores
- rutinas
- videos
- campañas
- automatización comercial
- SEO
- CRO
- WhatsApp
- personalización administrable

La experiencia debe ayudar al usuario a pasar de:

**“No sé qué comprar”**

a:

**“Entiendo qué necesito, confío en la recomendación y puedo comprarlo fácilmente.”**

La lógica global debe ser:

**Necesidad → Diagnóstico → Educación → Recomendación → Producto/Rutina → Compra → Recompra**

El proyecto debe inspirarse en la lógica estratégica del Método EcoDIEM, aplicándola a la venta online de Perfushopping.

---

# 1. PRINCIPIO TÉCNICO FUNDAMENTAL

NO rehacer el ecommerce innecesariamente.

ANTES DE PROGRAMAR:

1. inspeccionar el repositorio completo;
2. identificar frontend;
3. identificar backend;
4. identificar base de datos;
5. identificar ecommerce actual;
6. identificar catálogo;
7. identificar gestión de precios;
8. identificar stock;
9. identificar promociones;
10. identificar checkout;
11. identificar pagos;
12. identificar usuarios;
13. identificar Admin actual;
14. identificar roles y permisos;
15. identificar APIs;
16. identificar integraciones;
17. identificar SEO;
18. identificar Analytics;
19. identificar sistema actual de descripción generada por IA.

Documentar:

- arquitectura actual;
- componentes reutilizables;
- limitaciones;
- riesgos;
- qué mantener;
- qué extender;
- qué refactorizar;
- dependencias críticas.

No modificar grandes partes antes de presentar este diagnóstico.

---

# 2. NO CREAR UN SEGUNDO ADMIN

Perfushopping YA posee un Admin.

El nuevo sistema debe integrarse DENTRO DEL MISMO ADMIN.

NO crear:

- otro login;
- otro panel;
- otra navegación;
- otro sistema de usuarios;
- otro sistema de permisos;
- otra app administrativa.

La nueva funcionalidad debe sentirse como una evolución del Admin existente.

Reutilizar:

- sidebar;
- navbar;
- breadcrumbs;
- formularios;
- tablas;
- modales;
- filtros;
- paginación;
- uploads;
- diseño;
- permisos;
- sesión.

---

# 3. NUEVA ÁREA DENTRO DEL ADMIN

Agregar una sección equivalente a:

## CONTENIDO Y MARKETING

La ubicación exacta debe respetar el menú existente.

Submódulos sugeridos:

- Home
- Páginas y Landings
- Temas
- Necesidades
- Videos
- Artículos
- Rutinas
- Colecciones
- Campañas
- Recomendadores
- FAQs
- Banners
- CTAs
- Prioridades comerciales
- SEO
- Analytics comercial

No imponer esta jerarquía si el Admin actual tiene una estructura más conveniente.

---

# 4. PRINCIPIO DE PERSONALIZACIÓN TOTAL

No hardcodear ninguna marca.

No hardcodear ningún producto.

No hardcodear ninguna categoría comercial.

El sistema debe ser configurable para:

- cualquier marca;
- cualquier línea;
- cualquier categoría;
- cualquier subcategoría;
- cualquier producto;
- cualquier rutina;
- cualquier necesidad;
- cualquier campaña;
- cualquier segmento.

Las marcas hoy estratégicas pueden ser:

- Protenat
- Single
- Tierras del Volcán
- Biobelus

pero esto debe ser una configuración comercial desde Admin, no una regla técnica.

Evitar código del tipo:

```text
if brand == "Protenat"
```

La arquitectura debe ser:

**DATA-DRIVEN**

y no:

**BRAND-DRIVEN**

---

# 5. ENTIDADES COMERCIALES GENÉRICAS

Los bloques comerciales deben poder relacionarse con:

- brand
- product
- category
- subcategory
- routine
- topic
- need
- collection
- campaign
- article
- video
- custom_url

Usar IDs reales del sistema existente.

---

# 6. SINGLE SOURCE OF TRUTH

Datos transaccionales deben venir SIEMPRE del ecommerce existente:

- nombre;
- SKU;
- precio;
- stock;
- promociones;
- variantes;
- disponibilidad;
- imágenes principales cuando corresponda.

NO duplicar manualmente en el CMS:

- precio;
- stock;
- promociones.

El CMS agrega contenido comercial/editorial.

---

# 7. DESCRIPCIÓN ACTUAL GENERADA POR IA

Los productos ya cuentan con una descripción generada por IA.

NO eliminarla.

NO reemplazarla.

Debe mantenerse como contenido base.

Agregar una segunda capa:

## CONTENIDO COMERCIAL ENRIQUECIDO

Campos o bloques posibles:

- beneficio principal;
- ideal para;
- problema que resuelve;
- resultados esperados;
- modo de uso;
- consejo Perfushopping;
- video;
- rutina;
- productos complementarios;
- FAQ;
- CTA;
- contenido SEO adicional.

Lógica de prioridad:

**MANUAL OVERRIDE > AI GENERATED > DEFAULT**

Si un campo manual existe, tiene prioridad.

Si no existe, usar contenido generado por IA cuando sea viable.

---

# 8. IA DENTRO DEL ADMIN

Agregar, si la infraestructura actual lo permite:

## GENERAR SUGERENCIAS CON IA

La IA puede sugerir:

- beneficio principal;
- ideal para;
- problema que resuelve;
- beneficios;
- modo de uso simplificado;
- FAQ;
- tags;
- productos complementarios;
- SEO;
- CTA;
- clasificación comercial.

Toda sugerencia de IA debe quedar como borrador/revisión.

No publicar automáticamente contenido comercial crítico sin posibilidad de validación.

---

# 9. SCORE COMERCIAL DEL PRODUCTO

Agregar un indicador opcional:

## COMPLETITUD / SCORE COMERCIAL

Ejemplo:

**Ficha comercial: 78/100**

Considerar:

- descripción;
- beneficio principal;
- ideal para;
- modo de uso;
- video;
- FAQ;
- complementarios;
- rutina;
- SEO;
- imágenes;
- tags del recomendador.

Filtros en Admin:

- sólo descripción IA;
- contenido parcial;
- contenido completo;
- sin video;
- sin FAQ;
- sin rutina;
- alta prioridad;
- destacado.

Esto permite trabajar progresivamente sobre miles de SKUs.

---

# 10. EXTENDER LA FICHA DE PRODUCTO EXISTENTE

No crear otra ficha.

Agregar dentro del producto actual una sección o pestaña:

## VENTA ONLINE

Puede agrupar:

- descripción IA;
- score comercial;
- beneficio;
- ideal para;
- problema;
- resultados;
- modo de uso;
- videos;
- rutinas;
- complementarios;
- recomendación;
- tags;
- CTA;
- SEO adicional.

Pestañas sugeridas:

- Datos generales
- Precio / Stock
- Imágenes
- Descripción
- SEO
- Venta Online
- Recomendación
- Relaciones
- Videos
- Rutinas

Adaptar esto al diseño actual del Admin.

---

# 11. RELACIONES DE PRODUCTOS

Permitir relaciones:

- complement
- alternative
- replacement
- upgrade
- downgrade
- routine
- frequently_bought
- professional_version
- retail_version

Ejemplo:

Producto A → complemento → Producto B

Producto A → alternativa → Producto C

Producto A → upgrade → Producto D

---

# 12. VIDEOS

Crear entidad reutilizable:

## VIDEO

Campos:

- título;
- slug;
- descripción corta;
- descripción larga;
- thumbnail;
- fuente;
- URL;
- duración;
- protagonista/instructor;
- tema;
- categoría;
- tags;
- productos;
- rutinas;
- marcas;
- CTA;
- SEO;
- fecha publicación;
- orden;
- destacado;
- activo/inactivo.

Soportar inicialmente:

- YouTube
- Vimeo
- video externo/embed

Si la infraestructura lo permite:

- video alojado directamente.

Un video debe poder reutilizarse en:

- Home;
- producto;
- marca;
- artículo;
- rutina;
- campaña;
- landing;
- necesidad.

No duplicar contenido.

---

# 13. PÁGINA INDIVIDUAL DE VIDEO

Ruta sugerida:

`/aprende/videos/[slug]`

Debe mostrar:

- video;
- título;
- descripción;
- qué va a aprender;
- productos utilizados;
- comprar lo visto;
- rutinas relacionadas;
- artículos relacionados;
- videos relacionados;
- FAQ;
- CTA WhatsApp.

El contenido debe convertirse en oportunidad comercial.

---

# 14. TEMAS

Crear entidad:

## TOPIC

Ejemplos:

- Coloración
- Decoloración
- Reparación
- Hidratación
- Rulos
- Frizz
- Alisados
- Caída
- Tratamientos
- Skincare
- Consejos profesionales

Estos ejemplos NO deben hardcodearse.

Campos:

- nombre;
- slug;
- imagen;
- portada;
- descripción;
- SEO;
- productos;
- videos;
- artículos;
- rutinas;
- FAQ;
- orden;
- activo.

---

# 15. NECESIDADES / SOLUCIONES

Crear entidad:

## NEED

Ejemplos:

- Cabello dañado
- Sequedad
- Frizz
- Rubio/decolorado
- Rulos
- Color
- Caída
- Brillo

Cada necesidad puede tener:

- nombre;
- slug;
- imagen;
- icono;
- descripción corta;
- descripción larga;
- SEO;
- productos;
- marcas;
- rutinas;
- artículos;
- videos;
- FAQ;
- CTA;
- orden;
- activo.

Ruta:

`/soluciones/[slug]`

---

# 16. ARTÍCULOS / GUÍAS

Crear entidad:

## ARTICLE

Campos:

- título;
- slug;
- imagen;
- extracto;
- contenido;
- editor enriquecido;
- bloques;
- videos;
- productos relacionados;
- rutinas;
- marcas;
- tema;
- tags;
- CTA;
- FAQ;
- SEO;
- index/noindex;
- publicación programada;
- borrador/publicado.

Preferentemente modelo por bloques.

Tipos de bloques:

- texto;
- imagen;
- video;
- producto;
- colección;
- rutina;
- CTA;
- FAQ;
- comparación;
- quote;
- tip;
- pasos.

---

# 17. RUTINAS

Crear entidad:

## ROUTINE

Campos:

- título;
- slug;
- imagen;
- descripción;
- problema;
- resultado esperado;
- productos;
- orden de uso;
- instrucciones;
- precio calculado;
- descuento/promoción real si existe;
- video;
- artículos;
- FAQ;
- CTA;
- SEO.

Debe existir:

## RUTINA CURADA

Marketing define productos exactos.

## RUTINA DINÁMICA

El motor selecciona productos según reglas.

Nunca duplicar precio ni stock.

---

# 18. COLECCIONES

Crear entidad:

## COLLECTION

Ejemplos:

- Favoritos para rubios
- Los más vendidos
- Reparación económica
- Rutinas premium
- Elegidos por peluqueros
- Especial verano

Una colección puede usar:

- selección manual;
- filtros automáticos.

Ejemplo:

Marca = X  
Categoría = Máscaras  
Stock > 0  
Promoción = activa  
Orden = ventas

---

# 19. CAMPAÑAS

Crear entidad:

## CAMPAIGN

Puede involucrar:

- una o varias marcas;
- uno o varios productos;
- categorías;
- rutinas;
- videos;
- artículos;
- promociones;
- landings.

Campos:

- nombre;
- slug;
- objetivo;
- fecha inicio;
- fecha fin;
- segmento;
- marcas;
- productos;
- categorías;
- rutinas;
- artículos;
- videos;
- promoción;
- banner;
- hero;
- CTA;
- landing;
- UTM;
- estado.

---

# 20. PAGE BUILDER UNIVERSAL

Crear un Page Builder reutilizable.

Debe servir para:

- Home;
- marca;
- producto enriquecido;
- campaña;
- categoría;
- necesidad;
- landing;
- profesionales.

No crear un constructor distinto para cada tipo.

Bloques sugeridos:

- hero
- title
- text
- image
- video
- product
- product_grid
- product_carousel
- featured_product
- brand
- brand_grid
- routine
- routine_grid
- category
- category_grid
- topic
- article
- article_grid
- benefits
- faq
- reviews
- comparison
- before_after
- ingredients
- how_to
- banner
- promotion
- countdown
- newsletter
- form
- whatsapp
- cta
- custom_content

Cada bloque debe ser:

- editable;
- ordenable;
- duplicable;
- activable/desactivable;
- programable.

---

# 21. MODELO DE BLOQUE UNIVERSAL

Ejemplo conceptual:

```text
PageBlock

id
page_type
page_id
block_type
position
active
start_at
end_at
settings
content
target_entity_type
target_entity_id
segment
tracking_data
```

Adaptar al stack existente.

---

# 22. HOME — OBJETIVO

La Home no debe funcionar como listado masivo de productos.

Debe ayudar al usuario a elegir.

Prioridad:

**Necesidad → Solución → Recomendación → Compra**

y alternativamente:

**Producto → Complemento → Rutina → Compra**

---

# 23. HOME — ORDEN SUGERIDO

## 1. Header + buscador

## 2. Hero

Copy conceptual:

**Encontrá lo que realmente necesitás.**

Productos, rutinas y asesoramiento para elegir mejor.

CTA:

- Encontrar mi rutina
- Ver productos
- Consultar por WhatsApp

## 3. ¿Qué querés mejorar?

Cards de necesidades.

## 4. Recomendador

“¿No sabés qué elegir?”

## 5. Marcas recomendadas

Configurables desde Admin.

## 6. Rutinas recomendadas

## 7. Marca o producto destacado

Configurable dinámicamente.

## 8. Aprendé con Perfushopping

Videos + artículos.

## 9. Productos destacados

## 10. Profesionales

## 11. Promociones

## 12. Comprar por categoría

## 13. Prueba social

## 14. Instagram / UGC

## 15. Captación / Newsletter

## 16. Garantías

## 17. FAQ

## 18. CTA final

## 19. Footer

---

# 24. HOME ADMINISTRABLE

Dentro del Admin:

`Contenido y Marketing → Home`

Mostrar módulos como:

```text
☰ Hero                    ACTIVO
☰ Necesidades             ACTIVO
☰ Recomendador            ACTIVO
☰ Marca destacada         ACTIVO
☰ Producto destacado      ACTIVO
☰ Rutinas                 ACTIVO
☰ Videos                  ACTIVO
☰ Artículos               ACTIVO
☰ Profesionales           ACTIVO
☰ Promoción               ACTIVO
☰ Reseñas                 INACTIVO
```

Acciones:

- Editar
- Duplicar
- Ocultar
- Programar
- Eliminar
- Drag & drop

---

# 25. PRODUCTO DESTACADO INDIVIDUAL

Crear bloque:

## FEATURED PRODUCT

Campos:

- producto;
- título editorial;
- texto;
- imagen personalizada opcional;
- video;
- beneficio;
- CTA;
- mostrar precio;
- mostrar promo;
- mostrar stock;
- mostrar rating;
- agregar carrito;
- WhatsApp;
- layout.

Layouts:

- image-left
- image-right
- centered
- editorial
- compact

Los datos transaccionales se obtienen del ecommerce.

---

# 26. PÁGINAS DE MARCA

Cualquier marca debe poder tener página editorial:

`/marcas/[slug]`

Bloques:

- Hero
- Historia
- Beneficios
- Líneas
- Videos
- Productos destacados
- Rutinas
- Tutoriales
- Necesidades
- Testimonios
- Promoción
- FAQ
- CTA

No crear templates por nombre de marca.

---

# 27. EXPERIENCIA DE MARCA EN ADMIN

Si ya existe módulo de marcas:

extenderlo.

Agregar:

- logo;
- hero desktop;
- hero mobile;
- video;
- color principal;
- color secundario;
- texto;
- promesa;
- beneficios;
- productos destacados;
- colecciones;
- rutinas;
- videos;
- artículos;
- FAQ;
- CTA;
- SEO.

Los colores personalizados deben respetar límites del Design System global.

---

# 28. LANDINGS

Marketing debe crear landings sin tocar código.

Ejemplos:

- `/repara-tu-cabello`
- `/especial-coloracion`
- `/rubios`
- `/protenat-profesional`
- `/black-friday`
- `/producto-x`

Bloques:

- hero;
- texto;
- imagen;
- video;
- beneficios;
- producto;
- productos;
- rutina;
- testimonios;
- FAQ;
- countdown;
- CTA;
- banner;
- marcas;
- comparación;
- formulario;
- WhatsApp;
- carrusel.

---

# 29. RECOMENDADOR UNIVERSAL

Crear entidad:

## QUIZ

No crear un `HairQuiz` fijo.

Debe permitir múltiples recomendadores:

- rutina capilar;
- coloración;
- skincare;
- perfume;
- profesionales;
- kit de salón.

Ruta sugerida:

`/encontra-tu-rutina`

pero el motor debe ser genérico.

---

# 30. QUIZ — UX

Máximo recomendado inicialmente:

5 a 7 preguntas.

Mostrar progreso.

Permitir volver atrás.

Mobile first.

No usar lenguaje técnico innecesario.

Ejemplo:

**¿Cómo sentís tu pelo?**

mejor que:

**Seleccione la condición de la fibra capilar.**

---

# 31. PREGUNTAS DEL QUIZ

Ejemplos configurables:

- ¿Qué querés mejorar?
- ¿Cómo sentís tu cabello?
- ¿Tiene procesos químicos?
- ¿Cómo es tu cabello?
- ¿Usás calor?
- ¿Qué nivel de rutina buscás?
- ¿Qué presupuesto preferís?

Cada respuesta genera tags.

Ejemplo:

```text
need:damage
chemical:bleach
condition:dry
routine_level:complete
budget:balanced
```

---

# 32. MOTOR DE RECOMENDACIONES

Basado en:

**TAGS + REGLAS + PUNTAJE + PRIORIDAD**

No hardcodear resultados.

Crear entidad:

## RecommendationRule

Campos conceptuales:

- id;
- name;
- active;
- priority;
- conditions;
- excluded_conditions;
- target_type;
- target_id;
- score;
- start_at;
- end_at;
- segment;
- fallback.

Target puede ser:

- product;
- routine;
- collection;
- brand;
- category;
- content.

---

# 33. MATCH SCORE VS COMMERCIAL SCORE

Separar:

- Match Score
- Commercial Boost
- Final Score

Ejemplo:

```text
Match Score = 82
Commercial Boost = +5
Final Score = 87
```

Nunca permitir que un producto incompatible gane sólo por prioridad comercial.

Debe existir un `MIN_MATCH_SCORE` configurable.

La regla es:

**RELEVANCIA → CONFIANZA → RECOMENDACIÓN → VENTA**

---

# 34. PRODUCT TAGGING

Cada producto debe poder clasificarse con:

- necesidades;
- tipo de cabello;
- objetivos;
- procesos químicos;
- nivel de tratamiento;
- nivel de precio;
- uso profesional;
- paso de rutina;
- tags.

Pasos posibles:

- cleanse
- condition
- treat
- mask
- protect
- finish
- style

Debe ser extensible.

---

# 35. AI TAGGING

Agregar función:

## ANALIZAR PRODUCTO CON IA

Puede sugerir:

- necesidades;
- beneficios;
- tipo de cabello;
- paso de rutina;
- tratamiento;
- tags;
- complementarios.

Estado inicial:

**PENDIENTE DE REVISIÓN**

Permitir clasificación masiva por:

- marca;
- categoría;
- selección de productos.

Acciones:

- Aprobar
- Editar
- Rechazar

---

# 36. RESULTADO DEL QUIZ

Mostrar:

## Tu recomendación

Necesidades detectadas.

Luego:

## Tu rutina

Paso 1  
Producto recomendado

Paso 2  
Producto recomendado

Paso 3  
Producto recomendado

CTA:

**Agregar rutina completa al carrito**

CTA secundario:

**Comprar sólo lo esencial**

CTA:

**Ver alternativa más económica**

CTA:

**Ver opción más completa**

CTA WhatsApp:

**Quiero que revisen mi recomendación**

---

# 37. PRODUCTOS SIN STOCK

No recomendar como primera opción salvo configuración explícita.

Lógica:

```text
Producto recomendado sin stock
→ buscar replacement
→ buscar alternative
→ mostrar alternativa
```

---

# 38. WHATSAPP CONTEXTUAL

WhatsApp debe ser parte del funnel.

No enviar siempre:

“Hola, quiero información.”

Generar mensajes con contexto.

Desde producto:

“Hola, estoy viendo [PRODUCTO] y quería saber si es adecuado para mi caso.”

Desde necesidad:

“Hola, estoy buscando una solución para [NECESIDAD].”

Desde rutina:

“Hola, estoy viendo la rutina [RUTINA] y quería asesoramiento.”

Desde quiz:

Enviar resumen:

- necesidad;
- respuestas relevantes;
- rutina;
- productos recomendados.

Registrar:

`click_whatsapp`

con metadata.

---

# 39. PERFUSHOPPING PROFESIONAL

Crear landing:

`/profesionales`

Debe comunicar:

- productos profesionales;
- formatos;
- rendimiento;
- asesoramiento;
- promociones;
- capacitación;
- novedades;
- marcas;
- videos técnicos;
- contacto.

CTA:

- Quiero asesoramiento profesional
- Hablar con un asesor

---

# 40. BUSCADOR INTELIGENTE

No limitar a coincidencia por nombre de producto.

Debe buscar:

- productos;
- marcas;
- categorías;
- necesidades;
- artículos;
- videos;
- rutinas.

Ejemplo:

Usuario escribe:

**pelo seco**

Mostrar:

- Soluciones para cabello seco
- Productos
- Rutinas
- Contenido

---

# 41. SELECTOR UNIVERSAL EN ADMIN

Crear componente reutilizable:

## ENTITY SELECTOR

Debe buscar de forma asíncrona:

- productos;
- marcas;
- categorías;
- rutinas;
- temas;
- artículos;
- videos;
- campañas.

No cargar miles de productos en dropdowns.

Usar autocomplete/búsqueda asíncrona.

---

# 42. PRIORIDADES COMERCIALES

Agregar:

`Marketing → Prioridades comerciales`

Permitir configurar prioridad de:

- marca;
- producto;
- categoría;
- rutina;
- colección.

Campos:

- entidad;
- prioridad;
- fecha inicio;
- fecha fin;
- segmento;
- ubicación;
- objetivo;
- estado.

Esto permite impulsar cualquier marca o SKU sin modificar código.

---

# 43. ACCIÓN “DESTACAR”

Desde producto o marca:

## DESTACAR

Modal:

¿Dónde mostrar?

- Home
- Campaña
- Landing
- Artículo
- Categoría
- Necesidad

Campos:

- título;
- texto;
- CTA;
- imagen;
- inicio;
- fin;
- prioridad.

---

# 44. CRO

Aplicar:

- CTA visibles;
- mobile first;
- sticky add-to-cart cuando corresponda;
- checkout sin fricción;
- beneficios claros;
- confianza;
- envíos visibles;
- promociones entendibles;
- medios de pago;
- recomendaciones contextuales;
- WhatsApp contextual;
- popups no invasivos.

---

# 45. MOBILE FIRST

Prioridad absoluta en smartphone.

Verificar especialmente:

- Home;
- navegación;
- buscador;
- producto;
- videos;
- artículos;
- quiz;
- carrito;
- checkout;
- WhatsApp.

---

# 46. PERFORMANCE

Objetivos:

- buenos Core Web Vitals;
- imágenes WebP/AVIF;
- lazy loading;
- responsive images;
- caching;
- JS mínimo;
- bundles optimizados;
- SSR/SSG si el stack lo permite;
- consultas eficientes.

No sacrificar velocidad por animaciones.

---

# 47. SEO

Cada entidad pública debe permitir:

- slug;
- title;
- meta description;
- canonical;
- index/noindex;
- Open Graph;
- imagen social.

Implementar cuando corresponda:

- Product schema;
- Article schema;
- VideoObject;
- FAQ;
- BreadcrumbList.

Sitemap dinámico.

Mantener URLs actuales.

Usar 301 cuando sea necesario.

Evitar contenido duplicado.

---

# 48. ANALYTICS

Preparar eventos:

- view_product
- view_topic
- view_article
- play_video
- complete_video
- start_quiz
- quiz_answer
- quiz_abandon
- complete_quiz
- recommendation_view
- recommendation_product_click
- add_recommended_product
- add_routine_to_cart
- click_whatsapp
- professional_lead
- add_to_cart
- begin_checkout
- purchase
- search

Compatibilidad con:

- GA4;
- GTM;
- Meta Pixel;
- Google Ads.

No duplicar eventos existentes.

---

# 49. ANALYTICS COMERCIAL DEL RECOMENDADOR

Admin debe mostrar:

- usuarios que iniciaron;
- usuarios que completaron;
- tasa de finalización;
- pregunta con mayor abandono;
- necesidades más frecuentes;
- resultados frecuentes;
- productos más recomendados;
- productos más comprados;
- recomendación → carrito;
- recomendación → compra;
- ticket promedio.

También detectar:

## NECESIDADES SIN BUENA SOLUCIÓN

Esto puede servir para decisiones de surtido/compras.

---

# 50. ROLES Y PERMISOS

Integrar con el sistema existente.

Agregar permisos del tipo:

```text
marketing.home.view
marketing.home.edit

marketing.video.view
marketing.video.create
marketing.video.edit
marketing.video.delete

marketing.article.*
marketing.routine.*
marketing.campaign.*
marketing.quiz.*
marketing.seo.*

product.commercial_content.edit
product.recommendation.edit
```

Mapear a roles actuales.

No crear RBAC paralelo.

---

# 51. SEGURIDAD

Mantener y extender:

- autenticación;
- autorización server-side;
- validaciones;
- sanitización HTML;
- XSS;
- CSRF según stack;
- validación uploads;
- rate limits cuando corresponda;
- logs.

No confiar sólo en permisos frontend.

---

# 52. MODELO DE DATOS NUEVO

Evaluar entidades:

- Topic
- Need
- Video
- Article
- Routine
- RoutineItem
- FAQ
- LandingPage
- PageBlock
- CTA
- ContentTag
- Collection
- Campaign
- BrandPriority
- ProductRelation
- Quiz
- QuizQuestion
- QuizOption
- RecommendationRule
- SEOData

No duplicar entidades ya existentes.

Relacionarlas con Product, Brand y Category existentes.

---

# 53. MIGRACIONES

Toda modificación de base de datos debe usar migraciones versionadas.

Crear:

- migrations;
- seeds;
- rollback cuando sea viable.

No modificar producción manualmente.

---

# 54. API

Si la arquitectura actual usa API, mantener convenciones existentes.

Ejemplos conceptuales:

```text
GET /api/topics
GET /api/topics/:slug

GET /api/videos
GET /api/videos/:slug

GET /api/routines
GET /api/routines/:slug

POST /api/quiz/recommend
```

Admin:

```text
POST /api/admin/topics
PATCH /api/admin/topics/:id
DELETE /api/admin/topics/:id
```

No imponer REST si el sistema actual usa otro patrón.

---

# 55. TESTING

Agregar tests para funciones críticas:

- recomendaciones;
- reglas;
- relaciones;
- permisos;
- páginas públicas;
- endpoints;
- SEO;
- add routine to cart;
- reemplazos por falta de stock.

Después de cada fase:

- tests;
- lint;
- build;
- responsive;
- accesibilidad;
- SEO básico.

---

# 56. IMPLEMENTACIÓN POR FASES

## FASE 0 — DIAGNÓSTICO

- stack;
- arquitectura;
- Admin;
- permisos;
- catálogo;
- IA existente;
- SEO;
- Analytics;
- riesgos.

No modificar grandes partes.

---

## FASE 1 — FUNDACIONES

- modelo de datos;
- integración Admin;
- Page Builder base;
- Topics;
- Needs;
- Videos;
- Articles;
- relaciones.

---

## FASE 2 — PRODUCTO ENRIQUECIDO

- Venta Online;
- contenido comercial;
- descripción IA;
- score comercial;
- videos;
- FAQ;
- complementarios;
- tags;
- productos relacionados.

---

## FASE 3 — HOME

- Home Builder;
- Hero;
- Necesidades;
- destacados;
- rutinas;
- contenido;
- marcas;
- promociones;
- profesionales.

---

## FASE 4 — RUTINAS Y BUNDLES

- rutinas curadas;
- add all to cart;
- cross-sell;
- upsell;
- alternativas.

---

## FASE 5 — RECOMENDADOR

- Quiz engine;
- preguntas;
- respuestas;
- tags;
- reglas;
- resultados;
- WhatsApp contextual.

---

## FASE 6 — IA AVANZADA

- auto-tagging;
- sugerencias;
- clasificación masiva;
- scoring;
- rutinas dinámicas.

---

## FASE 7 — LANDINGS Y CAMPAÑAS

- Campaign;
- Landing Builder;
- colecciones;
- segmentación.

---

## FASE 8 — ANALYTICS / CRO

- dashboard;
- funnel;
- gaps;
- optimización;
- A/B-ready architecture.

---

# 57. CRITERIOS DE ACEPTACIÓN

La plataforma se considera correctamente implementada si Marketing puede, SIN tocar código:

1. seleccionar cualquier producto;
2. enriquecer su ficha;
3. agregar videos;
4. agregar FAQ;
5. relacionarlo con productos;
6. asociarlo a una rutina;
7. destacarlo en Home;
8. crear una landing;
9. asociarlo a una campaña;
10. incluirlo en artículos;
11. agregarlo a un recomendador;
12. quitarlo posteriormente;
13. hacer exactamente lo mismo con una marca nueva;
14. crear un nuevo quiz;
15. configurar nuevas necesidades;
16. crear contenido nuevo;
17. gestionar la Home;
18. programar campañas.

Además:

- un solo login;
- mismo Admin;
- mismo sistema de permisos;
- mismo catálogo;
- checkout actual preservado;
- precios y stock no duplicados;
- sin marcas hardcodeadas.

---

# 58. PRINCIPIO COMERCIAL FINAL

Perfushopping debe evolucionar desde:

**CATÁLOGO → PRODUCTO → CARRITO**

hacia:

**CONTENIDO → NECESIDAD → ASESORAMIENTO → RECOMENDACIÓN → PRODUCTO/RUTINA → COMPRA → RECOMPRA**

La sensación buscada para el usuario es:

> “No tengo que saber de antemano qué producto comprar. Perfushopping me ayuda a encontrarlo.”

El sistema debe funcionar como:

## UN EXPERTO QUE TE AYUDA A COMPRAR

y no como:

## UN CATÁLOGO DIGITAL LLENO DE PRODUCTOS

---

# 59. INSTRUCCIÓN INICIAL PARA OPENCODE

Antes de implementar grandes cambios:

Entregar:

1. stack detectado;
2. arquitectura actual;
3. arquitectura del Admin;
4. sistema de usuarios y permisos;
5. modelo actual relevante;
6. integración actual de la descripción IA;
7. limitaciones;
8. riesgos;
9. componentes reutilizables;
10. propuesta de arquitectura;
11. modelo de datos nuevo;
12. cambios en Admin;
13. nuevas rutas públicas;
14. mapa de componentes;
15. plan por fases;
16. estrategia de migración;
17. estrategia para no romper ecommerce, SEO ni checkout.

No hacer una reescritura completa sin justificación.

Priorizar implementación progresiva y compatible con producción.

---

# 60. RECOMENDADOR DE FRAGANCIAS

Además del recomendador capilar, el sistema debe incluir la capacidad de crear un recomendador específico para fragancias reutilizando el mismo motor genérico de QUIZ + TAGS + REGLAS + PUNTAJE.

NO crear una lógica aislada o hardcodeada exclusivamente para perfumes.

Debe implementarse como otro tipo de recomendador configurable desde:

`Contenido y Marketing → Recomendadores`

Ejemplo:

- Encontrá tu rutina capilar
- Encontrá tu fragancia ideal
- Encontrá tu skincare
- Armá tu kit profesional

---

## 60.1 OBJETIVO COMERCIAL

Ayudar a un cliente que no conoce nombres técnicos de perfumes a descubrir qué tipo de fragancia le gusta.

El flujo debe ir desde preferencias amplias hacia notas específicas.

Ejemplo:

**Familia olfativa → intensidad → sensación/estilo → ocasión → notas → recomendaciones**

El cliente no debería necesitar conocer de antemano notas técnicas.

---

## 60.2 PRIMER NIVEL — FAMILIA OLFATIVA

Primera pregunta sugerida:

# ¿Qué tipo de aroma te gusta más?

Opciones iniciales, configurables desde Admin:

- Cítricas
- Florales
- Amaderadas
- Orientales / Ambaradas
- Frescas
- Aromáticas
- Frutales
- Dulces / Gourmand
- Verdes
- Acuáticas

IMPORTANTE:

Estos valores deben almacenarse en tablas y NO hardcodearse.

Marketing debe poder:

- crear familias;
- editar nombre;
- editar descripción;
- agregar imagen/icono;
- activar/desactivar;
- cambiar orden;
- relacionarlas con fragancias;
- relacionarlas con notas.

---

# 61. TABLA AUXILIAR DE FAMILIAS OLFATIVAS

Crear entidad equivalente a:

## FragranceFamily

Campos conceptuales:

```text
id
name
slug
description
image
icon
active
sort_order
seo_title
seo_description
created_at
updated_at
```

Ejemplos:

```text
citrus
floral
woody
amber
fresh
aromatic
fruity
gourmand
green
aquatic
```

No asumir que esta lista será permanente.

Debe ser administrable.

---

# 62. INTENSIDAD DE FRAGANCIA

Segunda dimensión del recomendador:

# ¿Qué intensidad preferís?

Ejemplos configurables:

- Muy suave
- Suave
- Media
- Intensa
- Muy intensa

También puede expresarse comercialmente como:

- Fresca y liviana
- Equilibrada
- Con presencia
- Intensa y envolvente

Crear entidad o catálogo administrable:

## FragranceIntensity

Campos:

```text
id
name
slug
description
score
active
sort_order
```

El campo `score` puede ayudar al motor de recomendación.

---

# 63. NOTAS OLFATIVAS

Crear una tabla auxiliar centralizada de notas.

Entidad:

## FragranceNote

Campos conceptuales:

```text
id
name
slug
description
note_group
image
active
sort_order
created_at
updated_at
```

Ejemplos:

- Limón
- Bergamota
- Mandarina
- Naranja
- Pomelo
- Jazmín
- Rosa
- Lavanda
- Vainilla
- Ámbar
- Cedro
- Sándalo
- Pachouli
- Vetiver
- Almizcle
- Coco
- Caramelo
- Café
- Canela
- Manzana
- Pera
- Frutos rojos

NO guardar las notas como texto separado por comas en cada producto.

Normalizar mediante relaciones.

---

# 64. GRUPOS DE NOTAS

Crear opcionalmente:

## FragranceNoteGroup

Ejemplos:

- Cítricas
- Florales
- Maderas
- Especias
- Frutas
- Gourmand
- Aromáticas
- Verdes
- Resinas
- Almizcles

Campos:

```text
id
name
slug
description
active
sort_order
```

Una nota puede pertenecer a un grupo.

Ejemplo:

Bergamota → Cítricas

Jazmín → Florales

Cedro → Maderas

Vainilla → Gourmand

---

# 65. RELACIÓN FRAGANCIA ↔ NOTAS

Crear tabla relacional equivalente a:

## ProductFragranceNote

Campos:

```text
id
product_id
fragrance_note_id
note_position
weight
source
verified
created_at
updated_at
```

`note_position` debe admitir:

- top
- heart
- base
- unspecified

`weight` puede utilizarse opcionalmente para indicar relevancia relativa.

`source` puede indicar:

- manual
- ai
- imported
- brand
- catalog

`verified` indica si fue revisado por el equipo.

---

# 66. PIRÁMIDE OLFATIVA

Cada fragancia debe poder almacenar:

## NOTAS DE SALIDA

Ejemplos:

- Bergamota
- Limón
- Mandarina

## NOTAS DE CORAZÓN

Ejemplos:

- Jazmín
- Rosa
- Lavanda

## NOTAS DE FONDO

Ejemplos:

- Cedro
- Sándalo
- Vainilla
- Ámbar

Esto debe representarse mediante relaciones y no mediante texto libre únicamente.

La descripción textual puede seguir existiendo, pero las notas estructuradas deben ser independientes para permitir búsquedas y recomendaciones.

---

# 67. RELACIÓN FRAGANCIA ↔ FAMILIA OLFATIVA

Crear tabla:

## ProductFragranceFamily

Campos:

```text
product_id
fragrance_family_id
is_primary
weight
source
verified
```

Una fragancia puede pertenecer a más de una familia.

Ejemplo:

Producto X:

- Floral — primaria
- Frutal — secundaria
- Amaderada — secundaria

Esto permitirá recomendaciones más precisas.

---

# 68. PERFIL OLFATIVO DEL PRODUCTO

Dentro del Admin del producto, cuando corresponda a una fragancia, agregar pestaña o sección:

## PERFIL OLFATIVO

Campos:

- familia principal;
- familias secundarias;
- intensidad;
- notas de salida;
- notas de corazón;
- notas de fondo;
- dulzor;
- frescura;
- calidez;
- sensación;
- ocasión;
- estación;
- momento del día;
- público/estilo;
- tags.

No todos los campos deben ser obligatorios.

---

# 69. DIMENSIONES ADICIONALES OPCIONALES

Preparar el modelo para permitir atributos como:

## Dulzor

- bajo
- medio
- alto

## Frescura

- baja
- media
- alta

## Calidez

- baja
- media
- alta

## Persistencia percibida

- suave
- media
- alta

## Proyección percibida

- discreta
- moderada
- intensa

## Estación

- primavera
- verano
- otoño
- invierno
- todo el año

## Momento

- día
- noche
- ambos

## Ocasión

- diario
- oficina
- salida
- evento
- cita
- noche
- formal
- casual

Todos estos valores deben poder administrarse mediante catálogos/tags y no quedar fijados en el código.

---

# 70. FLUJO DEL QUIZ DE FRAGANCIAS

Ejemplo inicial:

## Pregunta 1

# ¿Qué tipo de aroma te gusta?

- Cítrico
- Floral
- Amaderado
- Dulce
- Fresco
- Frutal
- Intenso
- No sé

---

## Pregunta 2

# ¿Preferís una fragancia más fresca o con más presencia?

- Muy fresca y liviana
- Equilibrada
- Intensa
- Muy intensa

---

## Pregunta 3

# ¿Qué sensaciones te gustan más?

Ejemplos:

- Limpio
- Elegante
- Dulce
- Sensual
- Fresco
- Cálido
- Sofisticado
- Energético
- Suave
- Envolvente

Multi-select.

---

## Pregunta 4

# ¿Para qué ocasión la querés principalmente?

- Todos los días
- Trabajo / oficina
- Salidas
- Noche
- Citas
- Eventos
- Una fragancia versátil

---

## Pregunta 5

# ¿Qué notas te suelen gustar?

Mostrar sólo notas relevantes según respuestas previas.

Ejemplo:

Si eligió Cítricas:

- Bergamota
- Limón
- Mandarina
- Pomelo
- Naranja

Si eligió Florales:

- Jazmín
- Rosa
- Azahar
- Lavanda
- Peonía

Si eligió Amaderadas:

- Cedro
- Sándalo
- Vetiver
- Pachouli

La lista debe salir de tablas y relaciones.

No hardcodear las notas.

---

# 71. PREGUNTAS CONDICIONALES PARA FRAGANCIAS

El quiz debe utilizar branching.

Ejemplo:

SI familia = citrus

mostrar notas del grupo citrus.

SI familia = floral

mostrar notas florales.

SI intensidad = high

dar mayor score a fragancias intensas.

SI occasion = night

dar mayor score a productos etiquetados para noche.

---

# 72. MOTOR DE PUNTAJE PARA FRAGANCIAS

El mismo Recommendation Engine debe poder calcular compatibilidad utilizando:

- familia olfativa;
- intensidad;
- notas;
- sensación;
- ocasión;
- dulzor;
- frescura;
- estación;
- momento;
- otros tags.

Ejemplo conceptual:

```text
Familia primaria compatible      +40
Familia secundaria compatible    +20
Nota seleccionada                +15
Intensidad compatible            +15
Ocasión compatible               +10
Sensación compatible             +10
```

Los pesos deben poder configurarse.

---

# 73. RESULTADO DEL QUIZ DE FRAGANCIAS

No mostrar simplemente una grilla de perfumes.

Mostrar:

# Encontramos fragancias que encajan con vos

## Tu perfil

Ejemplo:

**Fresco + cítrico + intensidad media**

Te suelen gustar fragancias luminosas, frescas y fáciles de usar.

### Nuestra recomendación

[FRAGANCIA 1]

¿Por qué te la recomendamos?

- pertenece a la familia cítrica;
- tiene bergamota y mandarina;
- intensidad media;
- funciona bien para uso diario.

CTA:

[VER FRAGANCIA]

[AGREGAR AL CARRITO]

---

## También podrían gustarte

[FRAGANCIA 2]

[FRAGANCIA 3]

---

## Algo diferente para probar

Mostrar una opción cercana pero con un perfil ligeramente distinto.

Esto puede aumentar descubrimiento y ticket.

---

# 74. PERFIL DE NOTAS EN LA FICHA DE PRODUCTO

Cuando un producto sea una fragancia, permitir mostrar visualmente:

## PERFIL OLFATIVO

Familia:

**Cítrica / Amaderada**

Intensidad:

**Media**

Pirámide:

### Salida
Bergamota · Limón

### Corazón
Jazmín · Lavanda

### Fondo
Cedro · Almizcle

También mostrar:

**Te puede gustar si buscás:**

Fresco · Elegante · Diario

---

# 75. DESCUBRIMIENTO POR NOTA

Crear posibilidad de navegar por notas.

Ejemplo:

`/fragancias/notas/vainilla`

`/fragancias/notas/bergamota`

`/fragancias/notas/jazmin`

Cada página puede mostrar:

- explicación breve;
- fragancias relacionadas;
- familias asociadas;
- contenido;
- FAQs;
- productos destacados.

Debe poder configurarse si estas páginas son indexables o no.

---

# 76. DESCUBRIMIENTO POR FAMILIA

Crear páginas:

`/fragancias/familias/citricas`

`/fragancias/familias/florales`

`/fragancias/familias/amaderadas`

etc.

Pueden contener:

- descripción;
- características;
- ocasiones;
- notas típicas;
- productos;
- videos;
- artículos;
- FAQ;
- CTA al recomendador.

---

# 77. ADMIN DE FRAGANCIAS

Agregar dentro de:

`Contenido y Marketing`

una subsección o catálogo auxiliar equivalente a:

## FRAGANCIAS

- Familias olfativas
- Notas
- Grupos de notas
- Intensidades
- Sensaciones
- Ocasiones

Si estos catálogos encajan mejor dentro de una sección genérica de atributos/tags, reutilizarla.

No duplicar mecanismos existentes.

---

# 78. CARGA MASIVA

Debe poder cargarse información olfativa de forma eficiente.

Permitir:

- edición individual;
- edición masiva;
- importación CSV/XLSX si el sistema actual ya soporta imports;
- sugerencias por IA;
- aprobación masiva.

Ejemplo:

Producto | Familia | Intensidad | Salida | Corazón | Fondo | Estado

Esto es crítico porque puede haber muchas fragancias.

---

# 79. IA PARA FRAGANCIAS

Si existe IA en el sistema, agregar:

## ANALIZAR PERFIL OLFATIVO CON IA

Tomar como input:

- nombre del producto;
- marca;
- descripción actual;
- datos importados;
- ficha técnica;
- información del catálogo disponible.

Sugerir:

- familia principal;
- familias secundarias;
- intensidad;
- notas;
- grupos de notas;
- sensaciones;
- ocasión;
- tags.

Toda sugerencia debe quedar:

**PENDIENTE DE REVISIÓN**

La IA no debe inventar notas si no existe evidencia suficiente.

Cuando no pueda determinar algo:

dejarlo vacío o marcarlo para revisión.

---

# 80. RECOMENDACIONES CRUZADAS

Además de recomendar fragancias similares, permitir relaciones:

- similar_profile
- same_family
- same_note
- more_intense
- less_intense
- fresher
- sweeter
- alternative
- upgrade

Ejemplo:

“Si te gusta esta pero querés algo más intenso…”

“Si te gusta la vainilla pero querés algo más fresco…”

---

# 81. ANALYTICS DEL QUIZ DE FRAGANCIAS

Registrar:

- familias más seleccionadas;
- notas más seleccionadas;
- intensidad preferida;
- ocasiones;
- quizzes completados;
- fragancias más recomendadas;
- fragancias más compradas;
- recomendación → carrito;
- recomendación → compra;
- combinaciones frecuentes.

Esto puede servir para:

- compras;
- surtido;
- campañas;
- contenido;
- marcas a priorizar.

---

# 82. CRITERIO DE ACEPTACIÓN — FRAGANCIAS

El sistema se considera correctamente implementado si Marketing puede:

1. crear una nueva familia olfativa;
2. crear nuevas notas;
3. agrupar notas;
4. asociar notas a cualquier fragancia;
5. definir salida/corazón/fondo;
6. clasificar intensidad;
7. etiquetar ocasión/sensación;
8. crear un quiz de fragancias;
9. configurar preguntas condicionales;
10. configurar reglas;
11. recomendar cualquier producto del catálogo;
12. incorporar una nueva marca de perfumes sin programar;
13. editar todo desde el Admin actual;
14. importar o completar datos masivamente;
15. utilizar IA sólo como asistencia y con revisión humana.

La información olfativa debe quedar estructurada en tablas para permitir:

**FILTRAR + BUSCAR + RECOMENDAR + RELACIONAR + ANALIZAR**

y no limitarse a descripciones de texto.

---

# 83. ENRIQUECIMIENTO AUTOMÁTICO DE FRAGANCIAS MEDIANTE IA

Cuando un producto pertenezca a la categoría de fragancias/perfumería, el proceso de generación o enriquecimiento de descripción con IA debe intentar identificar automáticamente el perfil olfativo del producto.

El objetivo NO es sólo generar una descripción comercial.

La IA debe transformar información no estructurada en datos estructurados reutilizables por el sistema de recomendación.

Flujo esperado:

**Producto / nombre / descripción**
→ **identificación de la fragancia**
→ **búsqueda o análisis de información olfativa**
→ **detección de familia**
→ **detección de intensidad**
→ **detección de notas**
→ **clasificación estructurada**
→ **guardado en tablas**
→ **uso automático en recomendador**

---

# 84. DETECCIÓN AUTOMÁTICA DE PERFUME

Cuando se cree o actualice un producto de fragancias, la IA debe analizar al menos:

- nombre del producto;
- marca;
- línea;
- variante;
- descripción principal;
- descripción existente generada por IA;
- atributos disponibles;
- categoría;
- EAN/SKU si sirve para identificación;
- información de catálogo existente.

Intentar determinar de forma confiable cuál es la fragancia exacta.

Ejemplo conceptual:

```text
Marca: X
Producto: Eau de Parfum Y 100 ml
Descripción: ...
```

La IA debe intentar identificar:

```text
Fragrance identity: Y
Brand: X
Concentration: Eau de Parfum
```

No asociar datos olfativos si existen dudas significativas sobre la identidad exacta.

---

# 85. BÚSQUEDA / FUENTES DE INFORMACIÓN OLFATIVA

Si la arquitectura actual permite a la IA consultar información externa, implementar un servicio de enriquecimiento que busque datos olfativos en fuentes confiables.

Prioridad sugerida:

1. información oficial de la marca/fabricante;
2. distribuidor oficial;
3. ficha técnica del proveedor;
4. catálogos internos de Perfushopping;
5. fuentes externas especializadas confiables;
6. inferencia desde la descripción, sólo como último recurso.

La IA NO debe inventar notas.

Si no encuentra suficiente evidencia:

```text
status = needs_review
```

y dejar vacíos los campos no confirmados.

Guardar, cuando sea posible:

- fuente;
- URL/origen;
- fecha de consulta;
- nivel de confianza.

---

# 86. PIPELINE AUTOMÁTICO DE ENRIQUECIMIENTO

Crear un proceso equivalente a:

```text
1. Product created/updated
2. Detect category = fragrance
3. Extract brand + product identity
4. Search/resolve fragrance
5. Extract olfactory data
6. Normalize terminology
7. Match/create auxiliary records
8. Save relations
9. Calculate fragrance profile
10. Mark confidence/status
```

Debe poder ejecutarse:

- automáticamente al crear producto;
- automáticamente al regenerar descripción;
- manualmente desde Admin;
- masivamente por selección.

---

# 87. NORMALIZACIÓN DE NOTAS

La IA puede encontrar diferentes formas de escribir la misma nota.

Ejemplo:

```text
bergamot
bergamota
bergamotte
```

El sistema debe normalizar estas variantes hacia un único registro:

```text
FragranceNote: Bergamota
```

Lo mismo para:

```text
sandalwood → Sándalo
vanilla → Vainilla
jasmine → Jazmín
cedar → Cedro
patchouli → Pachouli
```

Crear aliases cuando sea necesario.

Entidad opcional:

## FragranceNoteAlias

Campos:

```text
id
fragrance_note_id
alias
language
source
active
```

Así se evita duplicar notas en las tablas.

---

# 88. CLASIFICACIÓN AUTOMÁTICA DE FAMILIA OLFATIVA

La IA debe identificar:

- familia principal;
- familias secundarias.

Ejemplo:

```text
primary_family = Floral
secondary_family = Amaderada
secondary_family = Almizclada
```

Guardar mediante:

`ProductFragranceFamily`

con:

```text
is_primary
weight
source
verified
confidence
```

---

# 89. CLASIFICACIÓN AUTOMÁTICA DE INTENSIDAD

Cuando exista información suficiente, estimar/clasificar:

- muy suave;
- suave;
- media;
- intensa;
- muy intensa.

La intensidad puede derivarse de:

- concentración;
- descripción oficial;
- perfil olfativo;
- información verificada disponible.

NO asumir automáticamente que toda Eau de Parfum tiene la misma intensidad.

Guardar nivel de confianza.

---

# 90. EXTRACCIÓN AUTOMÁTICA DE PIRÁMIDE OLFATIVA

La IA debe intentar extraer:

## Notas de salida
## Notas de corazón
## Notas de fondo

y relacionarlas con `FragranceNote`.

Ejemplo:

```text
Salida:
- Bergamota
- Mandarina

Corazón:
- Jazmín
- Rosa

Fondo:
- Vainilla
- Sándalo
- Almizcle
```

Guardar cada relación en:

`ProductFragranceNote`

con:

```text
product_id
fragrance_note_id
note_position
source
verified
confidence
```

---

# 91. PERFIL OLFATIVO DERIVADO

Una vez cargadas las familias y notas, el sistema puede generar automáticamente un perfil comercial derivado.

Ejemplo:

```text
Familia: Floral Amaderada
Intensidad: Media/Alta
Dulzor: Medio
Frescura: Media
Calidez: Alta
Uso sugerido: Noche / eventos
Sensaciones:
- elegante
- sensual
- envolvente
```

IMPORTANTE:

Distinguir entre:

- datos declarados/verificados;
- datos inferidos.

Guardar ambos con metadata de origen.

---

# 92. ESTADOS DE VERIFICACIÓN

Cada perfil olfativo debe tener estado:

```text
verified
high_confidence
medium_confidence
needs_review
not_found
```

Ejemplo de comportamiento:

## VERIFIED

Información validada manualmente o proveniente de fuente oficial.

## HIGH_CONFIDENCE

Coincidencia clara con varias fuentes confiables.

## MEDIUM_CONFIDENCE

Información probable pero requiere revisión.

## NEEDS_REVIEW

La IA encontró posibles datos pero existen dudas.

## NOT_FOUND

No se encontró información confiable.

---

# 93. INTERFAZ ADMIN — ENRIQUECIMIENTO DE PERFUME

Dentro de:

`Producto → Venta Online → Perfil Olfativo`

mostrar:

```text
PERFIL OLFATIVO
Estado: Alta confianza

Familia principal:
[Floral]

Familias secundarias:
[Amaderada] [Almizclada]

Intensidad:
[Media]

Notas de salida:
[Bergamota] [Mandarina]

Notas de corazón:
[Jazmín] [Rosa]

Notas de fondo:
[Vainilla] [Sándalo] [Almizcle]

Sensaciones:
[Elegante] [Sensual]

Fuente:
[ver fuentes]

Último análisis:
[fecha]
```

Acciones:

- Regenerar con IA
- Revisar
- Editar manualmente
- Confirmar
- Ver fuente
- Limpiar datos IA
- Buscar nuevamente

---

# 94. BOTÓN “COMPLETAR PERFIL OLFATIVO CON IA”

Agregar acción:

## COMPLETAR PERFIL OLFATIVO CON IA

La acción debe:

1. leer el producto;
2. identificar fragancia;
3. consultar información disponible;
4. detectar familia;
5. detectar intensidad;
6. detectar pirámide de notas;
7. normalizar notas;
8. relacionar tablas existentes;
9. crear nuevas notas sólo cuando corresponda;
10. guardar fuentes;
11. calcular confianza;
12. mostrar preview antes de confirmar, según configuración.

---

# 95. AUTOMATIZACIÓN AL GENERAR DESCRIPCIÓN

Si actualmente existe una función:

`GENERAR DESCRIPCIÓN CON IA`

extender su pipeline.

Al generar descripción para un perfume:

```text
GENERAR DESCRIPCIÓN
+
IDENTIFICAR PERFIL OLFATIVO
+
ESTRUCTURAR DATOS
+
GUARDAR RELACIONES
```

No ejecutar dos procesos desconectados si pueden compartir la misma identificación del producto.

Resultado esperado:

La descripción textual queda generada.

Y simultáneamente quedan almacenados:

- familia;
- intensidad;
- notas;
- pirámide;
- tags;
- perfil olfativo.

---

# 96. AUTOENRIQUECIMIENTO MASIVO

Crear una herramienta de Admin:

`Contenido y Marketing → Fragancias → Enriquecimiento IA`

Filtros:

- Sin perfil olfativo
- Perfil incompleto
- Baja confianza
- Sin familia
- Sin notas
- Marca
- Categoría
- Fecha
- Productos seleccionados

Acciones:

- Analizar seleccionados
- Reanalizar
- Aprobar alta confianza
- Exportar para revisión
- Ver errores

Para catálogos grandes, ejecutar por lotes/colas según la arquitectura existente.

No bloquear la interfaz durante procesos masivos.

---

# 97. EVITAR DUPLICADOS DE FRAGANCIAS

La IA debe intentar detectar si existen diferentes SKUs que representan la misma fragancia.

Ejemplo:

```text
Perfume X 30 ml
Perfume X 50 ml
Perfume X 100 ml
```

El perfil olfativo debería poder reutilizarse.

Evaluar crear entidad:

## FragranceProfile

Campos conceptuales:

```text
id
brand_id
name
canonical_name
concentration
primary_family_id
intensity_id
source
verification_status
confidence
created_at
updated_at
```

Luego relacionar:

```text
Product → FragranceProfile
```

Así múltiples presentaciones pueden compartir:

- familia;
- notas;
- intensidad;
- perfil.

Esto evita repetir información en cada SKU.

---

# 98. MODELO RECOMENDADO: PRODUCTO VS PERFIL DE FRAGANCIA

Separar conceptualmente:

## PRODUCT

Representa el SKU comercial.

Ejemplo:

```text
Perfume X EDP 100 ml
```

Incluye:

- precio;
- stock;
- SKU;
- volumen;
- promociones.

## FRAGRANCE PROFILE

Representa el perfume/aroma.

Ejemplo:

```text
Perfume X Eau de Parfum
```

Incluye:

- familia;
- notas;
- intensidad;
- sensaciones;
- ocasión;
- clasificación olfativa.

Relación:

```text
Product
→ fragrance_profile_id
```

De esta manera:

30 ml, 50 ml y 100 ml comparten un único perfil olfativo.

---

# 99. MOTOR DE SIMILITUD ENTRE FRAGANCIAS

Una vez estructurados los perfiles, crear capacidad para calcular similitud.

Ejemplo:

Cliente indica que le gusta:

```text
Floral
Amaderada
Vainilla
Jazmín
Intensidad media
```

El sistema compara esos atributos contra `FragranceProfile`.

Cada perfume obtiene un `match_score`.

Ejemplo:

```text
Fragancia A → 92%
Fragancia B → 86%
Fragancia C → 79%
```

La recomendación debe basarse en datos estructurados, no únicamente en similitud de texto.

---

# 100. RECOMENDACIÓN “SI TE GUSTA X”

Crear una experiencia adicional:

# ¿Qué perfume usás o te gusta actualmente?

El cliente puede buscar una fragancia.

Ejemplo:

```text
Me gusta: Fragancia X
```

El sistema recupera su `FragranceProfile`:

```text
Familia
Notas
Intensidad
Sensaciones
```

y busca dentro del catálogo de Perfushopping productos con mayor similitud.

Resultado:

# Si te gusta X, probablemente también te gusten:

1. Fragancia A — 91% compatible
2. Fragancia B — 85%
3. Fragancia C — 80%

Explicar brevemente:

“Comparten notas de vainilla y jazmín y un perfil floral cálido.”

Esto puede convertirse en un importante mecanismo de venta.

---

# 101. RECOMENDACIÓN DESDE PREFERENCIAS SIN CONOCER PERFUMES

El cliente también puede no conocer nombres.

Flujo:

```text
¿Qué aromas te gustan?
→ Cítricos

¿Qué intensidad?
→ Media

¿Qué notas preferís?
→ Bergamota + Mandarina

¿Cuándo la usarías?
→ Todos los días
```

El motor consulta tablas estructuradas y devuelve los productos con mayor afinidad.

---

# 102. EXPLICABILIDAD DE LA RECOMENDACIÓN

Cada recomendación debe explicar POR QUÉ apareció.

Ejemplo:

```text
Te recomendamos esta fragancia porque:

✓ elegiste aromas cítricos;
✓ contiene bergamota;
✓ tiene intensidad media;
✓ funciona bien para uso diario.
```

No mostrar simplemente:

“Recomendado para vos”.

Esto aumenta confianza y conversión.

---

# 103. ACTUALIZACIÓN AUTOMÁTICA DEL PERFIL

Cuando cambie:

- descripción;
- nombre;
- marca;
- ficha técnica;
- referencia del producto;

evaluar marcar el perfil como:

```text
needs_reanalysis
```

No sobrescribir automáticamente modificaciones manuales verificadas.

Regla:

**MANUAL VERIFIED > OFFICIAL SOURCE > AI HIGH CONFIDENCE > AI INFERENCE**

---

# 104. AUDITORÍA Y TRAZABILIDAD

Guardar historial mínimo:

- quién modificó;
- qué cambió;
- fecha;
- origen;
- IA/manual;
- fuente utilizada;
- confianza.

Esto es importante porque los datos estructurados alimentarán directamente las recomendaciones al cliente.

---

# 105. CRITERIO DE ACEPTACIÓN — ENRIQUECIMIENTO AUTOMÁTICO

El flujo se considera correcto si al cargar una nueva fragancia el sistema puede:

1. detectar que es una fragancia;
2. identificar marca y producto;
3. buscar información olfativa;
4. identificar familia;
5. identificar intensidad;
6. extraer notas;
7. separar salida/corazón/fondo;
8. normalizar nombres de notas;
9. relacionar registros auxiliares;
10. crear/reutilizar un FragranceProfile;
11. guardar nivel de confianza;
12. permitir revisión manual;
13. alimentar inmediatamente el recomendador;
14. permitir buscar fragancias similares;
15. compartir el mismo perfil entre distintas presentaciones/SKUs.

El objetivo final es convertir automáticamente una descripción textual en:

**DATOS OLFATIVOS ESTRUCTURADOS**

para poder hacer:

**BÚSQUEDA + FILTROS + SIMILITUD + RECOMENDACIÓN + PERSONALIZACIÓN + VENTA**

