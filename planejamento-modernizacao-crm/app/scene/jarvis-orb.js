/**
 * JARVIS & SEXTA-FEIRA · ORBE IARANDÚ 80K
 * Integrado ao Totem Central do Revenue OS (localhost:8090/inicio)
 * 80.000 partículas quânticas renderizadas via WebGL Shaders (Three.js)
 */
(function() {
  'use strict';

  const TOTAL_POINTS = 80000;
  const LOGO_POINTS_COUNT = 26000;

  let container = null;
  let canvas = null;
  let scene = null;
  let camera = null;
  let renderer = null;
  let particleGeometry = null;
  let particleMaterial = null;
  let particleSystem = null;
  let animFrameId = null;
  let isRunning = false;
  let isHovered = false;

  let uTime = 0;
  let audioAmp = 0.08;
  let audioBass = 0.05;
  let audioTreble = 0.06;
  let mouseX = 0;
  let mouseY = 0;
  let rawLogoPoints = null;
  let resizeObserver = null;

  async function loadLogoPoints() {
    if (rawLogoPoints && rawLogoPoints.length > 0) return rawLogoPoints;
    try {
      const res = await fetch('/scene/iarandu_points.json');
      if (res.ok) {
        rawLogoPoints = await res.json();
      }
    } catch (err) {
      console.warn('Usando fallback procedural para orbe IArandu:', err);
    }
    return rawLogoPoints;
  }

  function buildGeometry(logoPoints) {
    const geometry = new THREE.BufferGeometry();
    const positions = new Float32Array(TOTAL_POINTS * 3);
    const basePos = new Float32Array(TOTAL_POINTS * 3);
    const colors = new Float32Array(TOTAL_POINTS * 3);
    const sizes = new Float32Array(TOTAL_POINTS);
    const types = new Float32Array(TOTAL_POINTS);

    const hasLogo = logoPoints && logoPoints.length > 0;
    const logoLen = hasLogo ? logoPoints.length : 1;

    for (let i = 0; i < TOTAL_POINTS; i++) {
      let x, y, z;
      let r, g, b;
      let pSize, pType;

      if (i < LOGO_POINTS_COUNT) {
        // 1. LOGO DO IARANDÚ (ÁRVORE CEREBRAL NEURAL) - 26.000 PONTOS
        pType = 1.0;
        if (hasLogo) {
          const ptIdx = i % logoLen;
          const pt = logoPoints[ptIdx];
          const logoScale = 120.0;

          const jitterX = (Math.random() - 0.5) * 1.6;
          const jitterY = (Math.random() - 0.5) * 1.6;
          const depthZ = (Math.random() - 0.5) * 10.0 + Math.sin(pt[1] * 3.0) * 4.0;

          x = pt[0] * logoScale + jitterX;
          y = pt[1] * logoScale + jitterY + 4.0;
          z = depthZ;

          const brightness = pt[2] || 0.8;
          r = 1.0;
          g = 0.72 + brightness * 0.26;
          b = 0.12 + brightness * 0.25;
          pSize = 1.8 + brightness * 1.4;
        } else {
          const angle = (i / LOGO_POINTS_COUNT) * Math.PI * 2 * 10;
          const rad = 45 * Math.sin((i / LOGO_POINTS_COUNT) * Math.PI);
          x = Math.cos(angle) * rad;
          y = (i / LOGO_POINTS_COUNT) * 100 - 50;
          z = Math.sin(angle) * rad;
          r = 1.0; g = 0.85; b = 0.2;
          pSize = 2.0;
        }
      } else if (i < 65000) {
        // 2. ORBE ESFÉRICO QUÂNTICO EXTERNO (39.000 PONTOS)
        pType = 0.0;
        const pIdx = i - LOGO_POINTS_COUNT;
        const totalSphere = 65000 - LOGO_POINTS_COUNT;
        const phi = Math.acos(1 - 2 * (pIdx / totalSphere));
        const theta = Math.PI * (1 + Math.sqrt(5)) * pIdx;

        const radius = 88 + Math.random() * 16;
        x = radius * Math.sin(phi) * Math.cos(theta);
        y = radius * Math.sin(phi) * Math.sin(theta);
        z = radius * Math.cos(phi);

        r = 1.0;
        g = 0.45 + Math.random() * 0.35;
        b = 0.05 + Math.random() * 0.1;
        pSize = 1.3 + Math.random() * 1.5;
      } else if (i < 76000) {
        // 3. DISCO DE ACREÇÃO EQUATORIAL (11.000 PONTOS)
        pType = 2.0;
        const angle = Math.random() * Math.PI * 2;
        const dist = 95 + Math.pow(Math.random(), 0.6) * 95;
        x = Math.cos(angle) * dist;
        y = (Math.random() - 0.5) * 8;
        z = Math.sin(angle) * dist;

        const tilt = 0.35;
        const yNew = y * Math.cos(tilt) - z * Math.sin(tilt);
        const zNew = y * Math.sin(tilt) + z * Math.cos(tilt);
        y = yNew;
        z = zNew;

        if (dist > 145 && Math.random() < 0.28) {
          r = 0.0; g = 0.95; b = 1.0; // Fagulha ciano
        } else {
          r = 1.0; g = 0.55 + (dist / 190) * 0.35; b = 0.08;
        }
        pSize = 1.4 + Math.random() * 1.5;
      } else {
        // 4. CORONA EMBERS & FAÍSCAS ORBITAIS (4.000 PONTOS)
        pType = 3.0;
        const phi = Math.random() * Math.PI;
        const theta = Math.random() * Math.PI * 2;
        const radius = 105 + Math.random() * 95;
        x = radius * Math.sin(phi) * Math.cos(theta);
        y = radius * Math.sin(phi) * Math.sin(theta);
        z = radius * Math.cos(phi);

        r = 1.0; g = 0.35 + Math.random() * 0.5; b = 0.08;
        pSize = 1.8 + Math.random() * 2.2;
      }

      positions[i * 3] = x;
      positions[i * 3 + 1] = y;
      positions[i * 3 + 2] = z;

      basePos[i * 3] = x;
      basePos[i * 3 + 1] = y;
      basePos[i * 3 + 2] = z;

      colors[i * 3] = r;
      colors[i * 3 + 1] = g;
      colors[i * 3 + 2] = b;

      sizes[i] = pSize;
      types[i] = pType;
    }

    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('aBasePos', new THREE.BufferAttribute(basePos, 3));
    geometry.setAttribute('aColor', new THREE.BufferAttribute(colors, 3));
    geometry.setAttribute('aSize', new THREE.BufferAttribute(sizes, 1));
    geometry.setAttribute('aType', new THREE.BufferAttribute(types, 1));

    return geometry;
  }

  function createMaterial() {
    const vertexShader = `
      uniform float uTime;
      uniform float uAudioAmp;
      uniform float uAudioBass;
      uniform float uAudioTreble;
      uniform float uState; // 0=online, 1=listening, 2=processing, 3=speaking

      attribute vec3 aBasePos;
      attribute vec3 aColor;
      attribute float aSize;
      attribute float aType;

      varying vec3 vColor;

      void main() {
        vColor = aColor;
        vec3 pos = aBasePos;
        float t = uTime;

        if (aType > 0.5 && aType < 1.5) {
          // 1. DINÂMICA DA LOGO DO IARANDÚ (ÁRVORE NEURAL / CÉREBRO)
          float breathe = sin(t * 2.2) * 0.03 + (uAudioAmp * 0.22);
          pos.xy *= (1.0 + breathe);

          pos.z += sin(pos.y * 0.08 + t * 3.5) * (2.8 + uAudioBass * 7.0);

          float pulseY = mod(t * 50.0, 150.0) - 75.0;
          float distToPulse = abs(pos.y - pulseY);
          float synapse = smoothstep(14.0, 0.0, distToPulse);

          vec3 electricGlow = vec3(1.0, 1.0, 0.95);
          if (uState == 1.0) electricGlow = vec3(0.3, 1.0, 0.7);
          else if (uState == 3.0) electricGlow = vec3(1.0, 0.75, 0.2);

          vColor = mix(aColor, electricGlow, synapse * 0.85 + uAudioAmp * 0.55);

          pos.x += sin(pos.y * 0.6 + t * 14.0) * (uAudioTreble * 2.2);
        }
        else if (aType < 0.5) {
          // 2. ORBE ESFÉRICO QUÂNTICO EXTERNO
          float r = length(pos);
          float noise = sin(pos.x * 0.04 + t * 2.0) * cos(pos.y * 0.04 - t * 1.6) * sin(pos.z * 0.04 + t);
          float bassPulse = sin(r * 0.08 - t * 4.0) * (0.05 + uAudioBass * 0.35);
          float expansion = 1.0 + (uAudioAmp * 0.38) + (noise * 0.1) + bassPulse;
          pos *= expansion;

          float angle = t * 0.18 * (1.0 - r / 120.0);
          float ca = cos(angle); float sa = sin(angle);
          pos.xz = mat2(ca, -sa, sa, ca) * pos.xz;
        }
        else if (aType < 2.5) {
          // 3. DISCO DE ACREÇÃO EQUATORIAL
          float r = length(pos.xz);
          float speed = (22.0 / (r + 15.0)) * (1.0 + (uState == 2.0 ? 3.0 : 0.0));
          float rotAngle = t * speed;
          float c = cos(rotAngle); float s = sin(rotAngle);
          pos.xz = mat2(c, -s, s, c) * pos.xz;
          pos.y += sin(r * 0.12 - t * 3.5) * (4.5 + uAudioBass * 14.0);
        }
        else {
          // 4. EMBERS E FAÍSCAS ORBITAIS
          float drift = mod(t * 18.0 + pos.x * 2.0, 60.0);
          vec3 dir = normalize(pos);
          pos += dir * (drift + uAudioTreble * 35.0);
        }

        vec4 mvPosition = modelViewMatrix * vec4(pos, 1.0);
        gl_Position = projectionMatrix * mvPosition;

        float sizeMult = 1.0 + uAudioAmp * 1.4;
        if (aType > 0.5 && aType < 1.5) {
          sizeMult *= 1.35;
        }
        gl_PointSize = aSize * sizeMult * (285.0 / -mvPosition.z);
      }
    `;

    const fragmentShader = `
      varying vec3 vColor;

      void main() {
        vec2 coord = gl_PointCoord - vec2(0.5);
        float dist = length(coord);
        if (dist > 0.5) discard;

        float alpha = smoothstep(0.5, 0.05, dist);
        float coreGlow = smoothstep(0.28, 0.0, dist) * 0.85;
        vec3 finalCol = vColor + vec3(coreGlow);

        gl_FragColor = vec4(finalCol, alpha);
      }
    `;

    return new THREE.ShaderMaterial({
      vertexShader: vertexShader,
      fragmentShader: fragmentShader,
      uniforms: {
        uTime: { value: 0 },
        uAudioAmp: { value: 0.1 },
        uAudioBass: { value: 0.06 },
        uAudioTreble: { value: 0.07 },
        uState: { value: 0 }
      },
      transparent: true,
      blending: THREE.AdditiveBlending,
      depthWrite: false
    });
  }

  function resize() {
    if (!container || !renderer || !camera) return;
    const w = container.clientWidth || 194;
    const h = container.clientHeight || 288;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h, false);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  }

  function animate() {
    if (!isRunning) return;
    animFrameId = requestAnimationFrame(animate);

    uTime += 0.016;

    // Pulso autônomo orgânico contínuo (respiração do cérebro) + boost ao passar o mouse
    const pulse1 = Math.sin(uTime * 2.2) * 0.5 + 0.5;
    const pulse2 = Math.cos(uTime * 3.4) * 0.5 + 0.5;
    const baseAmp = isHovered ? 0.38 : (0.08 + pulse1 * 0.14);
    const baseBass = isHovered ? 0.32 : (0.06 + pulse2 * 0.10);
    const baseTreble = isHovered ? 0.35 : (0.07 + Math.sin(uTime * 4.1) * 0.06);

    audioAmp += (baseAmp - audioAmp) * 0.12;
    audioBass += (baseBass - audioBass) * 0.12;
    audioTreble += (baseTreble - audioTreble) * 0.12;

    if (particleMaterial) {
      particleMaterial.uniforms.uTime.value = uTime;
      particleMaterial.uniforms.uAudioAmp.value = audioAmp;
      particleMaterial.uniforms.uAudioBass.value = audioBass;
      particleMaterial.uniforms.uAudioTreble.value = audioTreble;
      particleMaterial.uniforms.uState.value = isHovered ? 3.0 : 0.0;
    }

    // Parallax 3D suave com o mouse
    camera.position.x += (mouseX * 22 - camera.position.x) * 0.06;
    camera.position.y += (mouseY * 16 - camera.position.y) * 0.06;
    camera.lookAt(scene.position);

    renderer.render(scene, camera);
  }

  async function mount(targetSelector) {
    if (typeof THREE === 'undefined') {
      console.warn('Three.js não carregado ainda; aguardando...');
      setTimeout(() => mount(targetSelector), 100);
      return;
    }

    const el = typeof targetSelector === 'string' ? document.querySelector(targetSelector) : targetSelector;
    if (!el) return;

    // Se já está montado no mesmo container e ativo, apenas retoma a animação
    if (container === el && renderer && isRunning) return;

    container = el;
    canvas = container.querySelector('canvas') || document.createElement('canvas');
    canvas.className = 'jarvis-canvas';
    if (!canvas.parentNode) container.appendChild(canvas);

    if (!scene) {
      scene = new THREE.Scene();
      camera = new THREE.PerspectiveCamera(50, (container.clientWidth || 194) / (container.clientHeight || 288), 0.1, 1500);
      camera.position.z = 295; // Enquadra perfeitamente na proporção vertical do totem

      renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
      renderer.setClearColor(0x000000, 0);

      const logoPoints = await loadLogoPoints();
      particleGeometry = buildGeometry(logoPoints);
      particleMaterial = createMaterial();
      particleSystem = new THREE.Points(particleGeometry, particleMaterial);
      scene.add(particleSystem);

      // Eventos de interação
      container.addEventListener('mousemove', (e) => {
        const r = container.getBoundingClientRect();
        mouseX = ((e.clientX - r.left) / r.width) * 2 - 1;
        mouseY = -(((e.clientY - r.top) / r.height) * 2 - 1);
      });
      container.addEventListener('mouseenter', () => { isHovered = true; });
      container.addEventListener('mouseleave', () => {
        isHovered = false;
        mouseX = 0;
        mouseY = 0;
      });

      if (window.ResizeObserver) {
        resizeObserver = new ResizeObserver(() => resize());
        resizeObserver.observe(container);
      }
      window.addEventListener('resize', resize);
    } else {
      // Reconecta o renderer ao novo canvas se necessário
      if (renderer.domElement !== canvas) {
        renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
        renderer.setClearColor(0x000000, 0);
      }
    }

    resize();
    start();
  }

  function start() {
    if (!isRunning) {
      isRunning = true;
      animate();
    }
  }

  function pause() {
    isRunning = false;
    if (animFrameId) {
      cancelAnimationFrame(animFrameId);
      animFrameId = null;
    }
  }

  // Pausa ao minimizar ou ocultar aba para poupar GPU
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      pause();
    } else if (container && document.body.contains(container)) {
      start();
    }
  });

  window.JarvisOrb = {
    mount,
    start,
    pause,
    resize,
    get isRunning() { return isRunning; }
  };
})();
