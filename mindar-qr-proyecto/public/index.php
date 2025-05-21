<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>AR BY-RNCORP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <script src="https://aframe.io/releases/1.5.0/aframe.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/mind-ar@1.2.5/dist/mindar-image-aframe.prod.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aframe-super-hands-component@4.0.5/dist/aframe-super-hands-component.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aframe-gesture-detector@3.3.0/dist/aframe-gesture-detector.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aframe-extras@6.1.1/dist/aframe-extras.min.js"></script>

  <style>
    html, body {
      margin: 0;
      padding: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      background: rgba(255, 255, 255, 0);
    }

    #ar-scene {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: block;
    }

    #loading-screen {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: black;
      color: white;
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
      font-family: sans-serif;
      font-size: 1.5em;
    }

    #reference-image {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 999;
      background: rgba(0, 0, 0, 0.7);
      padding: 10px;
      border-radius: 12px;
    }

    #reference-image img {
      max-width: 80vw;
      max-height: 50vh;
      border-radius: 10px;
    }

    #watermark {
      position: fixed;
      bottom: 10px;
      right: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(0, 0, 0, 0.4);
      padding: 6px 10px;
      border-radius: 10px;
      z-index: 9999;
      color: white;
      font-family: sans-serif;
      font-size: 0.9em;
    }

    #watermark img {
      height: 30px;
      opacity: 0.7;
    }
  </style>
</head>
<body>

<div id="loading-screen">Cargando...</div>



<div id="watermark">
  <img src="logo.png" alt="">
  Powered by RNcorp
</div>

<a-scene
  id="ar-scene"
  mindar-image="imageTargetSrc: targets1.mind;
                 filterMinCF:0.001; 
                 filterBeta:0.01;
                 missTolerance: 5;
                 warmupTolerance: 5;"
  vr-mode-ui="enabled: false"
  device-orientation-permission-ui="enabled: true"
  renderer="antialias: true; alpha: true; physicallyCorrectLights: true; colorManagement: true;"
  video="playsinline: true;"
  visible="false"
>
  <a-assets>
    <a-asset-item id="modelo-3d" src="./modelos/cat.glb"></a-asset-item>
  </a-assets>

  <a-camera position="0 0 0" look-controls="enabled: false" gesture-detector>
    <a-entity 
        cursor="rayOrigin: mouse; fuse: false;" 
        raycaster="objects: .interactable; far:50;"
        super-hands="colliderEvent: raycaster-intersection;
                     colliderEventProperty: els;
                     colliderEndEvent:raycaster-intersection-cleared;
                     colliderEndEventProperty: clearedEls;"
    ></a-entity>
  </a-camera>

  <a-entity mindar-image-target="targetIndex: 0">
    <a-entity id="model-container" position="0 -0.2 0">
      <a-gltf-model
        id="sharkModel"
        src="#modelo-3d"
        scale="6 6 6"
        class="interactable"
        animation-mixer
        gesture-handler="minScale: 0.5; maxScale: 20"
      ></a-gltf-model>
    </a-entity>
  </a-entity>
</a-scene>

<script>
  const scene = document.getElementById('ar-scene');
  const loadingScreen = document.getElementById('loading-screen');
  const referenceImage = document.getElementById('reference-image');
  const modelContainer = document.getElementById('model-container');
  const sharkModel = document.getElementById('sharkModel');

  let initialScale = { x: 6, y: 6, z: 6 };
  let initialDistance = null;
  let initialRotation = null;
  let isRotating = false;
  let isScaling = false;

  scene.addEventListener('renderstart', () => {
    loadingScreen.style.display = 'none';
    referenceImage.style.display = 'none'; // 👈 Oculta imagen de referencia cuando se inicia
    scene.setAttribute('visible', true);
  });

  scene.addEventListener('arReady', () => {
    console.log("MindAR is ready");
  });

  scene.addEventListener('arError', (event) => {
    console.error("MindAR Error:", event.detail);
    loadingScreen.innerHTML = "Error al iniciar AR. Verifica permisos de cámara.";
  });

  sharkModel.addEventListener('mousedown', startRotation);
  sharkModel.addEventListener('touchstart', startRotation);
  sharkModel.addEventListener('mousemove', handleRotation);
  sharkModel.addEventListener('touchmove', handleRotation);
  sharkModel.addEventListener('mouseup', stopRotation);
  sharkModel.addEventListener('touchend', stopRotation);

  scene.addEventListener('touchstart', (e) => {
    if (e.touches.length === 2) {
      isScaling = true;
      initialDistance = getDistance(e.touches[0], e.touches[1]);
      initialScale = sharkModel.getAttribute('scale');
    }
  });

  scene.addEventListener('touchmove', (e) => {
    if (isScaling && e.touches.length === 2) {
      const currentDistance = getDistance(e.touches[0], e.touches[1]);
      const scaleFactor = currentDistance / initialDistance;
      const newScale = {
        x: initialScale.x * scaleFactor,
        y: initialScale.y * scaleFactor,
        z: initialScale.z * scaleFactor
      };
      newScale.x = Math.max(0.5, Math.min(20, newScale.x));
      newScale.y = Math.max(0.5, Math.min(20, newScale.y));
      newScale.z = Math.max(0.5, Math.min(20, newScale.z));
      sharkModel.setAttribute('scale', newScale);
    }
  });

  scene.addEventListener('touchend', () => {
    isScaling = false;
  });

  function startRotation(e) {
    if (e.touches && e.touches.length > 1) return;
    isRotating = true;
    const rotation = modelContainer.getAttribute('rotation') || { x: 0, y: 0, z: 0 };
    initialRotation = {
      y: rotation.y,
      clientX: e.clientX || e.touches[0].clientX
    };
    e.preventDefault();
  }

  function handleRotation(e) {
    if (!isRotating) return;
    const clientX = e.clientX || (e.touches[0] && e.touches[0].clientX);
    if (!clientX) return;
    const deltaX = clientX - initialRotation.clientX;
    const rotationY = initialRotation.y + deltaX * 0.5;
    modelContainer.setAttribute('rotation', { x: 0, y: rotationY, z: 0 });
    e.preventDefault();
  }

  function stopRotation() {
    isRotating = false;
  }

  function getDistance(touch1, touch2) {
    const dx = touch1.clientX - touch2.clientX;
    const dy = touch1.clientY - touch2.clientY;
    return Math.sqrt(dx * dx + dy * dy);
  }
</script>

</body>
</html>
