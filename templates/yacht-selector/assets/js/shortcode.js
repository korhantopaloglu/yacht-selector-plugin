(function() {
  const canvas = document.getElementById('bgCanvas');
  const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setClearColor(0x000000, 0);

  const scene = new THREE.Scene();

  // Soft gradient background mesh
  const bgGeo = new THREE.PlaneGeometry(2, 2);
  const bgMat = new THREE.ShaderMaterial({
    depthWrite: false,
    uniforms: {
      uColor1: { value: new THREE.Color(0xd2fffc) },
      uColor2: { value: new THREE.Color(0xf8fffe) },
    },
    vertexShader: 'varying vec2 vUv; void main(){ vUv=uv; gl_Position=vec4(position,1.0); }',
    fragmentShader: 'uniform vec3 uColor1; uniform vec3 uColor2; varying vec2 vUv; void main(){ gl_FragColor=vec4(mix(uColor1,uColor2,vUv.y),1.0); }',
  });
  const bgMesh = new THREE.Mesh(bgGeo, bgMat);
  bgMesh.renderOrder = -1;
  scene.add(bgMesh);

  // Camera
  const camera = new THREE.PerspectiveCamera(50, 1, 0.1, 100);
  camera.position.set(0, 6, 14);
  camera.lookAt(0, 0, 0);

  // Dot grid — tight spacing, high contrast
  const cols = 180;
  const rows = 110;
  const spacing = 0.15;
  const count = cols * rows;

  const geometry = new THREE.BufferGeometry();
  const basePositions = new Float32Array(count * 3);
  const positions = new Float32Array(count * 3);
  const colors = new Float32Array(count * 3);
  const sizes = new Float32Array(count);

  const baseColor = new THREE.Color(0x2d7a73);
  for (let r = 0; r < rows; r++) {
    for (let c = 0; c < cols; c++) {
      const idx = r * cols + c;
      const i = idx * 3;
      basePositions[i] = (c - cols / 2) * spacing;
      basePositions[i + 1] = 0;
      basePositions[i + 2] = (r - rows / 2) * spacing;
      positions[i] = basePositions[i];
      positions[i + 1] = 0;
      positions[i + 2] = basePositions[i + 2];
      colors[i] = baseColor.r;
      colors[i + 1] = baseColor.g;
      colors[i + 2] = baseColor.b;
      sizes[idx] = 1.0;
    }
  }

  geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
  geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
  geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));

  // Custom shader material for per-point size + color
  const dotMat = new THREE.ShaderMaterial({
    transparent: true,
    uniforms: {
      uBaseSize: { value: 0.05 },
      uOpacity: { value: 0.95 },
      uPixelRatio: { value: renderer.getPixelRatio() },
    },
    vertexShader: [
      'attribute float size;',
      'varying vec3 vColor;',
      'uniform float uBaseSize;',
      'uniform float uPixelRatio;',
      'void main() {',
      '  vColor = color;',
      '  vec4 mv = modelViewMatrix * vec4(position, 1.0);',
      '  gl_PointSize = size * uBaseSize * uPixelRatio * (300.0 / -mv.z);',
      '  gl_Position = projectionMatrix * mv;',
      '}',
    ].join('\n'),
    fragmentShader: [
      'uniform float uOpacity;',
      'varying vec3 vColor;',
      'void main() {',
      '  float d = length(gl_PointCoord - 0.5);',
      '  if (d > 0.5) discard;',
      '  float a = smoothstep(0.5, 0.35, d);',
      '  gl_FragColor = vec4(vColor, a * uOpacity);',
      '}',
    ].join('\n'),
    vertexColors: true,
  });

  const dots = new THREE.Points(geometry, dotMat);
  scene.add(dots);

  /* ── Country coastline outlines ──
     Rendered as dotted coastline + harbor pins on the RIGHT/BOTTOM margin.
     Coords in positive x (right side of screen). */

  const countryData = {
    turkey: {
      label: 'TURKEY',
      // Turkish Aegean + Med coast — right side, lower area
      outline: [
        [7.5,1.0],[7.9,1.6],[8.3,2.0],[8.0,2.5],[7.6,2.9],
        [8.0,3.3],[8.5,3.7],[8.1,4.1],[7.7,4.5],[8.2,4.8],
        [8.7,5.1],[8.3,5.4],[7.8,5.0],[7.4,4.6],[7.8,4.2],
        [8.2,3.8],[8.6,3.4],[9.0,3.7],[9.4,4.0],[9.8,3.7],
        [10.2,4.0],[10.6,4.3],[11.0,4.0],[11.4,4.3],[11.8,4.6],
      ],
      harbors: {
        'Bodrum': [8.2, 4.8],
        'Marmaris': [8.7, 5.1],
        'Fethiye': [9.4, 4.0],
        'Gocek': [9.8, 3.7],
        'Antalya': [11.4, 4.3],
      },
    },
    greece: {
      label: 'GREECE',
      // Greek coast — right side
      outline: [
        [7.0,0.5],[7.4,1.2],[7.1,1.8],[6.7,2.4],[7.1,3.0],
        [7.6,3.5],[8.0,4.0],[7.6,4.5],[7.1,4.9],[7.5,5.3],
        [8.0,5.7],[8.5,5.3],[9.0,4.8],[8.6,4.3],[8.1,3.8],
        [7.7,3.3],[8.1,2.8],[8.5,2.3],[8.1,1.7],[7.6,1.2],
      ],
      harbors: {
        'Athens': [8.5, 2.3],
        'Mykonos': [9.0, 4.8],
        'Santorini': [8.6, 4.3],
        'Corfu': [6.7, 2.4],
        'Rhodes': [8.0, 5.7],
      },
    },
    croatia: {
      label: 'CROATIA',
      // Croatian Adriatic coast — right side
      outline: [
        [6.0,-0.5],[6.4,0.2],[6.1,0.9],[6.5,1.5],[6.2,2.1],
        [5.8,2.7],[6.2,3.3],[6.7,3.8],[6.3,4.3],[5.9,4.8],
        [5.5,5.3],[5.9,5.8],[6.3,6.3],[5.9,6.8],[5.5,7.3],
      ],
      harbors: {
        'Dubrovnik': [6.3, 4.3],
        'Split': [5.8, 2.7],
        'Hvar': [6.2, 3.3],
        'Zadar': [6.1, 0.9],
      },
    },
  };

  function interpolateOutline(pts, density) {
    const result = [];
    for (let i = 0; i < pts.length - 1; i++) {
      const [x0, z0] = pts[i];
      const [x1, z1] = pts[i + 1];
      const dist = Math.sqrt((x1 - x0) ** 2 + (z1 - z0) ** 2);
      const steps = Math.max(3, Math.round(dist * density));
      for (let s = 0; s < steps; s++) {
        const t = s / steps;
        result.push([x0 + (x1 - x0) * t, z0 + (z1 - z0) * t]);
      }
    }
    result.push(pts[pts.length - 1]);
    return result;
  }

  const countryObjects = {};

  Object.keys(countryData).forEach(key => {
    const cd = countryData[key];
    const coastPts = interpolateOutline(cd.outline, 12);
    const cGeo = new THREE.BufferGeometry();
    const cPos = new Float32Array(coastPts.length * 3);
    coastPts.forEach((p, i) => {
      cPos[i * 3] = p[0];
      cPos[i * 3 + 1] = 0.15;
      cPos[i * 3 + 2] = p[1];
    });
    cGeo.setAttribute('position', new THREE.BufferAttribute(cPos, 3));
    const cMat = new THREE.PointsMaterial({
      color: 0x0d9488, size: 0.12, sizeAttenuation: true,
      transparent: true, opacity: 0,
    });
    const cPoints = new THREE.Points(cGeo, cMat);
    scene.add(cPoints);

    const harborEntries = Object.entries(cd.harbors);
    const hGeo = new THREE.BufferGeometry();
    const hPos = new Float32Array(harborEntries.length * 3);
    harborEntries.forEach((entry, i) => {
      const p = entry[1];
      hPos[i * 3] = p[0];
      hPos[i * 3 + 1] = 0.25;
      hPos[i * 3 + 2] = p[1];
    });
    hGeo.setAttribute('position', new THREE.BufferAttribute(hPos, 3));
    const hMat = new THREE.PointsMaterial({
      color: 0xffffff, size: 0.35, sizeAttenuation: true,
      transparent: true, opacity: 0,
    });
    const hPoints = new THREE.Points(hGeo, hMat);
    scene.add(hPoints);

    const ringMeshes = [];
    harborEntries.forEach(entry => {
      const p = entry[1];
      const ringGeo = new THREE.RingGeometry(0.08, 0.12, 32);
      const ringMat = new THREE.MeshBasicMaterial({
        color: 0x0d9488, transparent: true, opacity: 0, side: THREE.DoubleSide,
      });
      const ring = new THREE.Mesh(ringGeo, ringMat);
      ring.position.set(p[0], 0.2, p[1]);
      ring.rotation.x = -Math.PI / 2;
      scene.add(ring);
      ringMeshes.push({ mesh: ring, mat: ringMat, baseX: p[0], baseZ: p[1] });
    });

    countryObjects[key] = {
      coast: { geo: cGeo, mat: cMat, points: cPoints, basePos: cPos },
      harbor: { geo: hGeo, mat: hMat, points: hPoints, basePos: hPos },
      rings: ringMeshes,
    };
  });

  let countryTargetAlpha = {};
  Object.keys(countryData).forEach(k => { countryTargetAlpha[k] = 0; });

  window.setBgCountry = function(country) {
    Object.keys(countryData).forEach(k => {
      countryTargetAlpha[k] = (country !== 'all' && k === country) ? 1.0 : 0;
    });
    const wm = document.getElementById('countryWatermark');
    if (wm) {
      wm.textContent = country !== 'all' && countryData[country] ? countryData[country].label : '';
    }
  };

  function onResize() {
    const w = window.innerWidth;
    const h = window.innerHeight;
    renderer.setSize(w, h);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    dotMat.uniforms.uPixelRatio.value = renderer.getPixelRatio();
  }
  onResize();
  window.addEventListener('resize', onResize);

  function waveY(bx, bz, t) {
    return Math.sin(bx * 1.2 + t * 1.2) * 0.12
      + Math.sin(bz * 0.9 + t * 0.9) * 0.1
      + Math.sin((bx + bz) * 0.7 + t * 0.7) * 0.07
      + Math.sin(bx * 2.5 - t * 1.5) * 0.04;
  }

  function animate(time) {
    requestAnimationFrame(animate);
    const t = time * 0.001;

    for (let r = 0; r < rows; r++) {
      for (let c = 0; c < cols; c++) {
        const i = (r * cols + c) * 3;
        positions[i + 1] = waveY(basePositions[i], basePositions[i + 2], t);
      }
    }
    geometry.attributes.position.needsUpdate = true;

    Object.keys(countryData).forEach(k => {
      const obj = countryObjects[k];
      const target = countryTargetAlpha[k];
      const coast = obj.coast;
      const harbor = obj.harbor;

      coast.mat.opacity += (target * 0.9 - coast.mat.opacity) * 0.08;
      harbor.mat.opacity += (target * 1.0 - harbor.mat.opacity) * 0.08;

      if (coast.mat.opacity > 0.005) {
        const cp = coast.geo.attributes.position.array;
        for (let i = 0; i < cp.length; i += 3) {
          cp[i + 1] = waveY(coast.basePos[i], coast.basePos[i + 2], t) + 0.15;
        }
        coast.geo.attributes.position.needsUpdate = true;
      }

      if (harbor.mat.opacity > 0.005) {
        const hp = harbor.geo.attributes.position.array;
        for (let i = 0; i < hp.length; i += 3) {
          hp[i + 1] = waveY(harbor.basePos[i], harbor.basePos[i + 2], t) + 0.3
            + Math.sin(t * 1.8 + i) * 0.04;
        }
        harbor.geo.attributes.position.needsUpdate = true;
      }

      obj.rings.forEach((ring, ri) => {
        const ringAlpha = coast.mat.opacity;
        ring.mat.opacity = ringAlpha * 0.6 * (0.5 + 0.5 * Math.sin(t * 2.0 + ri * 1.5));
        const pulse = 1.0 + 0.3 * Math.sin(t * 2.0 + ri * 1.5);
        ring.mesh.scale.set(pulse, pulse, 1);
        ring.mesh.position.y = waveY(ring.baseX, ring.baseZ, t) + 0.22;
      });
    });

    renderer.render(scene, camera);
  }
  requestAnimationFrame(animate);
})();

/* ══════════════════════════════════════
   DATA
   ══════════════════════════════════════ */
const monthNames = [
  { name: 'Jan', full: 'January' }, { name: 'Feb', full: 'February' },
  { name: 'Mar', full: 'March' }, { name: 'Apr', full: 'April' },
  { name: 'May', full: 'May' }, { name: 'Jun', full: 'June' },
  { name: 'Jul', full: 'July' }, { name: 'Aug', full: 'August' },
  { name: 'Sep', full: 'September' }, { name: 'Oct', full: 'October' },
  { name: 'Nov', full: 'November' }, { name: 'Dec', full: 'December' },
];

const occData = {
  all: [23, 31, 45, 58, 72, 89, 95, 88, 64, 42, 35, 78],
  greece: [15, 20, 35, 52, 78, 92, 98, 94, 68, 38, 22, 55],
  turkey: [30, 38, 50, 60, 70, 85, 90, 82, 58, 45, 40, 82],
  croatia: [18, 25, 42, 55, 75, 91, 97, 90, 62, 35, 28, 70],
};

const yachtDB = {
  greece: [
    { name: 'Almila', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/almila.jpg', model: 'Gulet', size: '24m x 6m', motor: '2x250hp', year: '2018', port: 'Athens', cabins: 4, guests: 8, crew: 3, amenities: ['WiFi', 'Jacuzzi', 'SUP', 'Snorkeling'], months: [4,5,6,7,8,9] },
    { name: 'Bella', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/bella.jpg', model: 'Gulet', size: '20m x 5.5m', motor: '2x200hp', year: '2021', port: 'Mykonos', cabins: 3, guests: 6, crew: 2, amenities: ['WiFi', 'Kayak', 'Snorkeling'], months: [3,4,5,6,7,8,9,10] },
    { name: 'Double Eagle', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/double-eagle.jpg', model: 'Motor Yacht', size: '30m x 7m', motor: '2x400hp', year: '2020', port: 'Santorini', cabins: 5, guests: 10, crew: 4, amenities: ['Jacuzzi', 'Jet Ski', 'Gym', 'Spa', 'Cinema'], months: [0,1,2,3,4,5,6,7,8,9,10,11] },
    { name: 'Gulmaria', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/gulmaria.jpg', model: 'Gulet', size: '22m x 5.5m', motor: '2x180hp', year: '2019', port: 'Corfu', cabins: 3, guests: 6, crew: 2, amenities: ['WiFi', 'Kayak', 'Fishing'], months: [5,6,7,8] },
    { name: 'Casa Dell Arte', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/casa-dell-arte.jpg', model: 'Gulet', size: '26m x 6m', motor: '2x300hp', year: '2022', port: 'Rhodes', cabins: 4, guests: 8, crew: 3, amenities: ['WiFi', 'SUP', 'Jacuzzi', 'Drone'], months: [4,5,6,7,8,9,10] },
  ],
  turkey: [
    { name: 'Dea Del Mare', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/dea-del-mare.jpg', model: 'Gulet', size: '28m x 6m', motor: '2x280hp', year: '2009 /21', port: 'Bodrum', cabins: 5, guests: 10, crew: 4, amenities: ['İnternet, TV', 'Bot (Joker 90hp)', 'Minder, Duş', 'Knee, Kano, Jetski', 'SUP', 'Snorkeling'], months: [0,1,2,3,4,5,6,7,8,9,10,11] },
    { name: 'Good Life', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/good-life.jpg', model: 'Gulet', size: '35m x 7.5m', motor: '2x450hp', year: '2020', port: 'Gocek', cabins: 6, guests: 12, crew: 5, amenities: ['WiFi', 'Jacuzzi', 'Water Ski', 'Jet Ski', 'SUP'], months: [3,4,5,6,7,8,9,10] },
    { name: 'Calm Down', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/calm-down.jpg', model: 'Gulet', size: '26m x 6m', motor: '2x300hp', year: '2017', port: 'Marmaris', cabins: 4, guests: 8, crew: 3, amenities: ['WiFi', 'SUP', 'Snorkeling', 'Kayak'], months: [4,5,6,7,8,9] },
    { name: 'Freedom', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/freedom.jpg', model: 'Gulet', size: '24m x 5.5m', motor: '2x220hp', year: '2015', port: 'Fethiye', cabins: 4, guests: 8, crew: 3, amenities: ['WiFi', 'Jacuzzi', 'Fishing', 'Kayak'], months: [4,5,6,7,8,9,10] },
    { name: 'Bellamare', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/bellamare.jpg', model: 'Gulet', size: '30m x 7m', motor: '2x350hp', year: '2019', port: 'Bodrum', cabins: 5, guests: 10, crew: 4, amenities: ['WiFi', 'Jacuzzi', 'Jet Ski', 'Diving'], months: [3,4,5,6,7,8,9,10] },
    { name: 'Cobra King', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/cobra-king.jpg', model: 'Motor Yacht', size: '22m x 5.5m', motor: '2x400hp', year: '2023', port: 'Antalya', cabins: 3, guests: 6, crew: 2, amenities: ['WiFi', 'Jacuzzi', 'Drone', 'SUP'], months: [5,6,7,8,9] },
    { name: 'Bedia Sultan', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/bedia-sultan.jpg', model: 'Gulet', size: '32m x 7m', motor: '2x300hp', year: '2012/22', port: 'Bodrum', cabins: 6, guests: 12, crew: 5, amenities: ['WiFi', 'Jacuzzi', 'Water Ski', 'Kano'], months: [0,1,2,3,4,5,6,7,8,9,10,11] },
    { name: 'Cielo', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/cielo.jpg', model: 'Gulet', size: '25m x 6m', motor: '2x260hp', year: '2016', port: 'Gocek', cabins: 4, guests: 8, crew: 3, amenities: ['WiFi', 'SUP', 'Snorkeling', 'Fishing'], months: [4,5,6,7,8,9] },
  ],
  croatia: [
    { name: 'Arabella', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/arabella.jpg', model: 'Gulet', size: '24m x 6m', motor: '2x250hp', year: '2018', port: 'Dubrovnik', cabins: 4, guests: 8, crew: 3, amenities: ['WiFi', 'Jacuzzi', 'Drone', 'SUP'], months: [3,4,5,6,7,8,9,10] },
    { name: 'Babylon', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/babylon.jpg', model: 'Gulet', size: '28m x 6.5m', motor: '2x300hp', year: '2020', port: 'Split', cabins: 5, guests: 10, crew: 4, amenities: ['WiFi', 'Jacuzzi', 'Water Ski', 'Jet Ski'], months: [4,5,6,7,8,9] },
    { name: 'FX 38', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/fx-38.jpg', model: 'Motor Yacht', size: '38m x 8m', motor: '2x600hp', year: '2024', port: 'Hvar', cabins: 6, guests: 12, crew: 5, amenities: ['Cinema', 'Gym', 'Spa', 'Jet Ski', 'Helipad'], months: [0,1,2,3,4,5,6,7,8,9,10,11] },
    { name: 'DE Love', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/de-love.jpg', model: 'Gulet', size: '22m x 5.5m', motor: '2x200hp', year: '2019', port: 'Zadar', cabins: 3, guests: 6, crew: 2, amenities: ['WiFi', 'Kayak', 'Fishing', 'SUP'], months: [5,6,7,8] },
    { name: 'Alfa Mario', img: '/wp-content/plugins/yacht-selector-plugin/templates/assets/yachts/alfa-mario.jpg', model: 'Gulet', size: '26m x 6m', motor: '2x280hp', year: '2021', port: 'Dubrovnik', cabins: 4, guests: 8, crew: 3, amenities: ['WiFi', 'Jacuzzi', 'Snorkeling', 'Drone'], months: [3,4,5,6,7,8,9,10] },
  ],
};

function buildMonths(country) {
  const occ = occData[country] || occData.all;
  return monthNames.map((m, i) => ({ ...m, occ: occ[i] }));
}

function getYachts(country, monthIdx) {
  let pool = [];
  if (country === 'all') {
    ['turkey', 'greece', 'croatia'].forEach(c => pool.push(...yachtDB[c]));
  } else {
    pool = yachtDB[country] || [];
  }
  const available = pool.filter(y => y.months.includes(monthIdx));
  return available.length > 0 ? available : pool.slice(0, 3);
}

function occColor(pct) {
  if (pct <= 30) return '#c7ff39';
  if (pct <= 50) return '#ffdb39';
  if (pct <= 70) return '#ff7e39';
  if (pct <= 85) return '#ff3946';
  return '#ff0000';
}

let months = buildMonths('all');

function createMonthItem(m) {
  const el = document.createElement('div');
  el.className = 'picker-item';
  const fillWidth = Math.max(10, m.occ) + '%';
  const fillColor = occColor(m.occ);
  el.innerHTML =
    '<div class="month-name">' + m.name + '</div>' +
    '<div class="occ-bar-track"><div class="occ-bar-fill"></div></div>' +
    '<div class="occ-pct">' + m.occ + '%</div>';
  const fill = el.querySelector('.occ-bar-fill');
  fill.style.setProperty('--occ-fill-width', fillWidth);
  fill.style.setProperty('--occ-fill-color', fillColor);
  return el;
}

function createMonthPicker(wrapperId, data_, defaultIndex, onChange) {
  let data = data_;
  const wrapper = document.getElementById(wrapperId);
  const track = wrapper.querySelector('.picker-track');
  const smallWidth = 80;
  const bigWidth = 120;
  const wrapperCenter = wrapper.offsetWidth / 2;
  const N = data.length;
  const copies = 5;
  const totalItems = N * copies;
  const centerCopyStart = N * 2;

  for (let c = 0; c < copies; c++) {
    data.forEach(m => track.appendChild(createMonthItem(m)));
  }

  const allItems = track.querySelectorAll('.picker-item');
  const distOpacity = [1, 1, 1, 1, 1];
  let virtualIndex = centerCopyStart + defaultIndex;
  let startX = 0;
  let startTranslate = 0;
  let isDragging = false;
  let hasMoved = false;

  function getTranslate(vi) {
    return wrapperCenter - (vi * smallWidth + bigWidth / 2);
  }
  function getDragTranslate(vi) {
    return wrapperCenter - (vi * smallWidth + smallWidth / 2);
  }
  function realIndex(vi) {
    return ((vi % N) + N) % N;
  }

  function recenter() {
    const ri = realIndex(virtualIndex);
    const newVi = centerCopyStart + ri;
    if (newVi === virtualIndex) return;
    virtualIndex = newVi;
    track.classList.add('no-transition');
    updateStyles(virtualIndex);
    track.style.transform = 'translateX(' + getTranslate(virtualIndex) + 'px)';
    track.offsetHeight;
    track.classList.remove('no-transition');
  }

  function updateStyles(vi) {
    allItems.forEach((el, i) => {
      el.classList.remove('selected');
      if (i === vi) {
        el.classList.add('selected');
      }
      el.style.opacity = '';
      el.style.transform = '';
    });
  }

  function snapTo(vi) {
    vi = Math.max(N, Math.min(totalItems - N - 1, vi));
    virtualIndex = vi;
    updateStyles(vi);
    track.style.transform = 'translateX(' + getTranslate(vi) + 'px)';
    onChange(data[realIndex(vi)], realIndex(vi));
  }

  function snapToAnimated(vi) {
    snapTo(vi);
    setTimeout(recenter, 380);
  }

  function onPointerDown(e) {
    recenter();
    isDragging = true;
    hasMoved = false;
    startX = e.clientX || e.touches?.[0]?.clientX || 0;
    allItems.forEach(el => el.classList.remove('selected'));
    startTranslate = getDragTranslate(virtualIndex);
    track.classList.add('dragging');
    track.style.transform = 'translateX(' + startTranslate + 'px)';
    e.preventDefault();
  }

  function onPointerMove(e) {
    if (!isDragging) return;
    const x = e.clientX || e.touches?.[0]?.clientX || 0;
    const diff = x - startX;
    if (Math.abs(diff) > 3) hasMoved = true;
    track.style.transform = 'translateX(' + (startTranslate + diff) + 'px)';
    const rawVi = (wrapperCenter - (startTranslate + diff)) / smallWidth - 0.5;
    const nearest = Math.round(rawVi);
    if (nearest >= 0 && nearest < totalItems) {
      allItems.forEach((el, i) => {
        el.classList.remove('selected');
        const dist = Math.abs(i - nearest);
        const op = dist < distOpacity.length ? distOpacity[dist] : 0.05;
        el.style.opacity = op;
      });
      onChange(data[realIndex(nearest)], realIndex(nearest));
    }
  }

  function onPointerUp() {
    if (!isDragging) return;
    isDragging = false;
    track.classList.remove('dragging');
    const match = track.style.transform.match(/translateX\((.+?)px\)/);
    const cur = match ? parseFloat(match[1]) : startTranslate;
    snapToAnimated(Math.round((wrapperCenter - cur) / smallWidth - 0.5));
  }

  track.addEventListener('click', e => {
    if (hasMoved) return;
    const item = e.target.closest('.picker-item');
    if (!item) return;
    const ci = Array.from(allItems).indexOf(item);
    if (ci >= 0) snapToAnimated(ci);
  });

  wrapper.addEventListener('mousedown', onPointerDown);
  wrapper.addEventListener('touchstart', onPointerDown, { passive: false });
  document.addEventListener('mousemove', onPointerMove);
  document.addEventListener('touchmove', onPointerMove, { passive: false });
  document.addEventListener('mouseup', onPointerUp);
  document.addEventListener('touchend', onPointerUp);

  wrapper.addEventListener('wheel', e => {
    e.preventDefault();
    const dir = e.deltaX !== 0 ? Math.sign(e.deltaX) : Math.sign(e.deltaY);
    snapToAnimated(virtualIndex + dir);
  }, { passive: false });

  snapTo(virtualIndex);

  function updateData(newData) {
    data = newData;
    allItems.forEach((el, i) => {
      const ri = realIndex(i);
      const m = data[ri];
      el.querySelector('.month-name').textContent = m.name;
      el.querySelector('.occ-pct').textContent = m.occ + '%';
      const fill = el.querySelector('.occ-bar-fill');
      fill.style.setProperty('--occ-fill-width', Math.max(10, m.occ) + '%');
      fill.style.setProperty('--occ-fill-color', occColor(m.occ));
    });
    onChange(data[realIndex(virtualIndex)], realIndex(virtualIndex));
  }

  return {
    snapTo: ri => snapToAnimated(centerCopyStart + ri),
    getValue: () => data[realIndex(virtualIndex)],
    updateData,
  };
}

let currentYachts = [];
let activeYacht = 0;
let currentCountry = 'all';
let currentMonthIdx = 6;
let yachtPicker = null;

function createYachtItem(yacht) {
  const el = document.createElement('div');
  el.className = 'yacht-picker-item';
  const img = document.createElement('img');
  img.src = yacht.img;
  img.alt = yacht.name;
  img.loading = 'lazy';
  el.appendChild(img);
  return el;
}

function createYachtPicker(wrapperId, data_, defaultIndex, onChange) {
  let data = data_;
  const wrapper = document.getElementById(wrapperId);
  const track = document.getElementById('yachtPickerTrack');
  track.innerHTML = '';

  const distSpecs = [
    { w: 328, h: 421, r: 20, offset: 0 },
    { w: 328, h: 421, r: 20, offset: 371 },
    { w: 272, h: 351, r: 20, offset: 449 },
    { w: 200, h: 257, r: 10, offset: 505 },
    { w: 154, h: 197, r: 10, offset: 538 },
  ];
  const maxDist = 4;

  const N = data.length;
  if (N === 0) return null;
  const copies = 5;
  const totalItems = N * copies;
  const centerCopyStart = N * 2;

  for (let c = 0; c < copies; c++) {
    data.forEach(y => track.appendChild(createYachtItem(y)));
  }

  let allItems = Array.from(track.querySelectorAll('.yacht-picker-item'));
  let virtualIndex = centerCopyStart + defaultIndex;
  let startX = 0;
  let dragOffset = 0;
  let isDragging = false;
  let hasMoved = false;

  function realIndex(vi) {
    return ((vi % N) + N) % N;
  }

  function positionItems(centerVi, fractional) {
    allItems.forEach((el, i) => {
      const rawDist = i - centerVi + (fractional || 0);
      const absDist = Math.abs(rawDist);
      const side = rawDist < 0 ? -1 : 1;

      if (absDist < 0.01) {
        el.style.width = distSpecs[0].w + 'px';
        el.style.height = distSpecs[0].h + 'px';
        el.style.opacity = '0';
        el.style.zIndex = '5';
        el.style.borderRadius = distSpecs[0].r + 'px';
        el.style.transform = 'translate(-50%, -50%)';
        el.style.pointerEvents = 'none';
        return;
      }

      if (absDist > maxDist + 0.5) {
        el.style.opacity = '0';
        el.style.pointerEvents = 'none';
        el.style.transform = 'translate(-50%, -50%) translateX(' + (side * 600) + 'px)';
        return;
      }

      const floor = Math.max(1, Math.floor(absDist));
      const ceil = Math.min(maxDist, Math.ceil(absDist));
      const t = absDist - Math.floor(absDist);

      const s0 = distSpecs[floor];
      const s1 = floor === ceil ? s0 : distSpecs[ceil];

      const w = s0.w + (s1.w - s0.w) * t;
      const h = s0.h + (s1.h - s0.h) * t;
      const r = s0.r + (s1.r - s0.r) * t;
      const off = s0.offset + (s1.offset - s0.offset) * t;
      const z = maxDist + 1 - Math.round(absDist);

      let opacity = 1;
      if (absDist > maxDist) {
        opacity = Math.max(0, 1 - (absDist - maxDist) * 2);
      }

      el.style.width = Math.round(w) + 'px';
      el.style.height = Math.round(h) + 'px';
      el.style.opacity = opacity;
      el.style.zIndex = z;
      el.style.borderRadius = Math.round(r) + 'px';
      el.style.transform = 'translate(-50%, -50%) translateX(' + (side * off) + 'px)';
      el.style.pointerEvents = opacity > 0.3 ? 'auto' : 'none';
    });
  }

  function recenter() {
    const ri = realIndex(virtualIndex);
    const newVi = centerCopyStart + ri;
    if (newVi === virtualIndex) return;
    virtualIndex = newVi;
    track.classList.add('no-transition');
    positionItems(virtualIndex, 0);
    void track.offsetHeight;
    track.classList.remove('no-transition');
  }

  function snapTo(vi) {
    vi = Math.max(N, Math.min(totalItems - N - 1, vi));
    virtualIndex = vi;
    positionItems(vi, 0);
    onChange(data[realIndex(vi)], realIndex(vi));
  }

  function snapToAnimated(vi) {
    vi = Math.max(N, Math.min(totalItems - N - 1, vi));
    virtualIndex = vi;
    track.classList.remove('no-transition');
    positionItems(vi, 0);
    onChange(data[realIndex(vi)], realIndex(vi));
    setTimeout(recenter, 500);
  }

  let pointerDown = false;

  function onPointerDown(e) {
    pointerDown = true;
    isDragging = false;
    hasMoved = false;
    startX = e.clientX || e.touches?.[0]?.clientX || 0;
    dragOffset = 0;
    if (e.type === 'touchstart') e.preventDefault();
  }

  function enterDragMode(currentX) {
    recenter();
    isDragging = true;
    startX = currentX;
    dragOffset = 0;
    track.classList.add('dragging');
  }

  function onPointerMove(e) {
    if (!pointerDown) return;
    const x = e.clientX || e.touches?.[0]?.clientX || 0;
    if (!isDragging) {
      if (Math.abs(x - startX) > 5) {
        hasMoved = true;
        enterDragMode(x);
      }
      return;
    }
    const diff = x - startX;
    const fractional = -diff / 200;
    dragOffset = fractional;
    positionItems(virtualIndex, fractional);

    const nearest = Math.round(virtualIndex + fractional);
    if (nearest >= 0 && nearest < totalItems) {
      onChange(data[realIndex(nearest)], realIndex(nearest));
    }
  }

  function onPointerUp() {
    if (!pointerDown) return;
    pointerDown = false;
    if (!isDragging) return;
    isDragging = false;
    track.classList.remove('dragging');
    const targetVi = Math.round(virtualIndex + dragOffset);
    snapToAnimated(targetVi);
  }

  track.addEventListener('click', e => {
    if (hasMoved) return;
    const item = e.target.closest('.yacht-picker-item');
    if (!item) return;
    const ci = allItems.indexOf(item);
    if (ci >= 0 && ci !== virtualIndex) snapToAnimated(ci);
  });

  wrapper.addEventListener('mousedown', onPointerDown);
  wrapper.addEventListener('touchstart', onPointerDown, { passive: false });
  document.addEventListener('mousemove', onPointerMove);
  document.addEventListener('touchmove', onPointerMove, { passive: false });
  document.addEventListener('mouseup', onPointerUp);
  document.addEventListener('touchend', onPointerUp);

  wrapper.addEventListener('wheel', e => {
    e.preventDefault();
    const dir = e.deltaX !== 0 ? Math.sign(e.deltaX) : Math.sign(e.deltaY);
    snapToAnimated(virtualIndex + dir);
  }, { passive: false });

  snapTo(virtualIndex);

  return {
    snapTo: ri => snapToAnimated(centerCopyStart + ri),
    getValue: () => data[realIndex(virtualIndex)],
    rebuild(newData) {
      data = newData;
      track.innerHTML = '';
      for (let c = 0; c < copies; c++) {
        data.forEach(y => track.appendChild(createYachtItem(y)));
      }
      allItems = Array.from(track.querySelectorAll('.yacht-picker-item'));
      virtualIndex = centerCopyStart;
      snapTo(virtualIndex);
    },
  };
}

let crossDissolveLayer = 'A';

function updateYachtDetail(yacht) {
  if (!yacht) return;

  const imgA = document.getElementById('yachtImgA');
  const imgB = document.getElementById('yachtImgB');
  const currentImg = crossDissolveLayer === 'A' ? imgA : imgB;
  const nextImg = crossDissolveLayer === 'A' ? imgB : imgA;

  if (currentImg.src && !currentImg.src.endsWith('/') && currentImg.getAttribute('data-current') !== yacht.img) {
    const fromSrc = currentImg.src;
    const fxActive = typeof triggerTransition === 'function' && fromSrc
      && document.getElementById('fxSelect')
      && document.getElementById('fxSelect').value !== 'none';

    if (fxActive) {
      triggerTransition(fromSrc, yacht.img, function onCanvasReady() {
        nextImg.src = yacht.img;
        nextImg.setAttribute('data-current', yacht.img);
        nextImg.style.transition = 'none';
        nextImg.style.zIndex = '2';
        currentImg.style.zIndex = '1';
        nextImg.style.opacity = '1';
        currentImg.style.opacity = '0';
        void nextImg.offsetHeight;
        nextImg.style.transition = '';
        currentImg.style.transition = '';
      });
    } else {
      nextImg.src = yacht.img;
      nextImg.setAttribute('data-current', yacht.img);
      nextImg.style.zIndex = '2';
      currentImg.style.zIndex = '1';
      nextImg.style.opacity = '1';
      currentImg.style.opacity = '0';
    }
    crossDissolveLayer = crossDissolveLayer === 'A' ? 'B' : 'A';
  } else if (!currentImg.getAttribute('data-current')) {
    currentImg.src = yacht.img;
    currentImg.setAttribute('data-current', yacht.img);
    currentImg.style.opacity = '1';
  }

  document.getElementById('yachtName').textContent = yacht.name;

  document.getElementById('specsLeft').innerHTML =
    '<div class="spec-row"><span class="spec-label">Model</span><span class="spec-value">' + yacht.model + '</span></div>' +
    '<div class="spec-row"><span class="spec-label">Ölçüler</span><span class="spec-value">' + yacht.size + '</span></div>' +
    '<div class="spec-row"><span class="spec-label">Motor</span><span class="spec-value">' + yacht.motor + '</span></div>' +
    '<div class="spec-row"><span class="spec-label">Üretim /R</span><span class="spec-value">' + yacht.year + '</span></div>' +
    '<div class="spec-row"><span class="spec-label">Liman</span><span class="spec-value">' + yacht.port + '</span></div>';

  document.getElementById('specsCenter').innerHTML =
    '<div class="stat-block"><div class="stat-number">' + yacht.cabins + '</div><div class="stat-label">Kabin</div></div>' +
    '<div class="stat-block"><div class="stat-number">' + yacht.guests + '</div><div class="stat-label">Misafir</div></div>' +
    '<div class="stat-block"><div class="stat-number">' + yacht.crew + '</div><div class="stat-label">Mürettebat</div></div>';

  const max = 4;
  const a = yacht.amenities || [];
  let html = a.slice(0, max).map(x => '<div class="amenity">' + x + '</div>').join('');
  if (a.length > max) html += '<div class="amenity more">+' + (a.length - max) + ' Fazlası</div>';
  document.getElementById('specsRight').innerHTML = html;
}

function refreshYachts() {
  currentYachts = getYachts(currentCountry, currentMonthIdx);
  activeYacht = 0;
  yachtPicker = createYachtPicker('yachtPickerWrapper', currentYachts, 0, function(yacht, idx) {
    activeYacht = idx;
    updateYachtDetail(yacht);
  });
  if (currentYachts.length > 0) {
    updateYachtDetail(currentYachts[0]);
  }
}

document.addEventListener('keydown', e => {
  if (e.key === 'ArrowLeft' && yachtPicker) yachtPicker.snapTo(((activeYacht - 1) + currentYachts.length) % currentYachts.length);
  if (e.key === 'ArrowRight' && yachtPicker) yachtPicker.snapTo((activeYacht + 1) % currentYachts.length);
});

document.getElementById('yachtNavLeft').addEventListener('click', () => {
  if (yachtPicker) yachtPicker.snapTo(((activeYacht - 1) + currentYachts.length) % currentYachts.length);
});
document.getElementById('yachtNavRight').addEventListener('click', () => {
  if (yachtPicker) yachtPicker.snapTo((activeYacht + 1) % currentYachts.length);
});

const centerMonthEl = document.getElementById('centerMonth');
const centerBarEl = document.getElementById('centerBar');
const centerPctEl = document.getElementById('centerPct');
const centerPctLabelEl = document.getElementById('centerPctLabel');

function occLabel(occ) {
  if (occ >= 90) return 'Full';
  if (occ >= 70) return 'High';
  if (occ >= 50) return 'Mid';
  return 'Low';
}

function updateCenter(item, monthIdx) {
  centerMonthEl.textContent = item.full;
  centerBarEl.style.width = Math.max(10, item.occ) + '%';
  centerBarEl.style.background = occColor(item.occ);
  const c = occColor(item.occ);
  centerPctEl.textContent = item.occ + '%';
  centerPctEl.style.color = c;
  centerPctLabelEl.textContent = occLabel(item.occ);
  centerPctLabelEl.style.color = c;
  if (monthIdx !== currentMonthIdx) {
    currentMonthIdx = monthIdx;
    refreshYachts();
  }
}

const picker = createMonthPicker('monthPicker', months, 6, updateCenter);

document.querySelectorAll('#countryTabs .country-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('#countryTabs .country-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    currentCountry = tab.dataset.country;
    if (typeof setBgCountry === 'function') setBgCountry(currentCountry);
    months = buildMonths(currentCountry);
    picker.updateData(months);
    refreshYachts();
  });
});

refreshYachts();

function alignPicker() {
  const visual = document.getElementById('yachtVisual');
  const header = document.querySelector('.frosted-header');
  if (!visual) return;
  const vRect = visual.getBoundingClientRect();
  const headerH = header ? header.offsetHeight : 0;
  const exposedCenter = vRect.top + headerH + (vRect.height - headerH) / 2;
  const wrapper = document.getElementById('yachtPickerWrapper');
  wrapper.style.top = exposedCenter + 'px';
  wrapper.style.transform = 'translateY(-50%)';
}
alignPicker();
window.addEventListener('resize', alignPicker);

(function() {
  const canvas = document.getElementById('transitionCanvas');
  const visual = document.getElementById('yachtVisual');
  const ctx = canvas.getContext('2d');

  function resize() {
    canvas.width = visual.offsetWidth;
    canvas.height = visual.offsetHeight;
  }
  resize();
  window.addEventListener('resize', resize);

  function hash(x, y) {
    return ((Math.sin(x * 127.1 + y * 311.7) * 43758.5453) % 1 + 1) % 1;
  }

  function drawCover(c, img, dx, dy, dw, dh) {
    var iw = img.naturalWidth || img.width;
    var ih = img.naturalHeight || img.height;
    var scale = Math.max(dw / iw, dh / ih);
    var sw = dw / scale;
    var sh = dh / scale;
    var sx = (iw - sw) / 2;
    var sy = (ih - sh) / 2;
    c.drawImage(img, sx, sy, sw, sh, dx, dy, dw, dh);
  }

  const effects = {
    'cross-dissolve': {
      label: 'Cross Dissolve',
      render: function(c, i1, i2, p, w, h) {
        c.globalAlpha = 1;
        drawCover(c, i1, 0, 0, w, h);
        c.globalAlpha = p;
        drawCover(c, i2, 0, 0, w, h);
        c.globalAlpha = 1;
      },
    },
    'directional-wipe': {
      label: 'Directional Wipe',
      render: function(c, i1, i2, p, w, h) {
        drawCover(c, i1, 0, 0, w, h);
        var edge = w * 0.06;
        var x = p * (w + edge) - edge;
        c.save();
        c.beginPath();
        c.rect(0, 0, x, h);
        c.clip();
        drawCover(c, i2, 0, 0, w, h);
        c.restore();
      },
    },
    'radial-wipe': {
      label: 'Radial Wipe',
      render: function(c, i1, i2, p, w, h) {
        drawCover(c, i1, 0, 0, w, h);
        var maxR = Math.sqrt(w * w + h * h) / 2;
        c.save();
        c.beginPath();
        c.arc(w / 2, h / 2, p * maxR, 0, Math.PI * 2);
        c.clip();
        drawCover(c, i2, 0, 0, w, h);
        c.restore();
      },
    },
    blinds: {
      label: 'Blinds',
      render: function(c, i1, i2, p, w, h) {
        drawCover(c, i1, 0, 0, w, h);
        var strips = 10;
        var sw = w / strips;
        c.save();
        c.beginPath();
        for (var s = 0; s < strips; s++) {
          var delay = s / strips * 0.3;
          var sp = Math.max(0, Math.min(1, (p - delay) / 0.7));
          if (sp > 0) c.rect(s * sw, 0, sw * sp, h);
        }
        c.clip();
        drawCover(c, i2, 0, 0, w, h);
        c.restore();
      },
    },
    pixelate: {
      label: 'Pixelate',
      render: function(c, i1, i2, p, w, h) {
        var blockSize = Math.max(1, Math.round(Math.sin(p * Math.PI) * 40 + 1));
        drawCover(c, p < 0.5 ? i1 : i2, 0, 0, w, h);
        c.imageSmoothingEnabled = false;
        var tw = Math.max(1, Math.round(w / blockSize));
        var th = Math.max(1, Math.round(h / blockSize));
        c.drawImage(canvas, 0, 0, w, h, 0, 0, tw, th);
        c.drawImage(canvas, 0, 0, tw, th, 0, 0, w, h);
        c.imageSmoothingEnabled = true;
      },
    },
    'noise-dissolve': {
      label: 'Noise Dissolve',
      render: function(c, i1, i2, p, w, h) {
        if (!effects['noise-dissolve']._noise || effects['noise-dissolve']._nw !== w) {
          var cols = Math.ceil(w / 8);
          var rows = Math.ceil(h / 8);
          var n = [];
          for (var r = 0; r < rows; r++) {
            for (var cl = 0; cl < cols; cl++) {
              n.push(hash(cl, r));
            }
          }
          effects['noise-dissolve']._noise = n;
          effects['noise-dissolve']._cols = cols;
          effects['noise-dissolve']._rows = rows;
          effects['noise-dissolve']._nw = w;
        }
        drawCover(c, i1, 0, 0, w, h);
        var noise = effects['noise-dissolve']._noise;
        var colsCount = effects['noise-dissolve']._cols;
        var rowsCount = effects['noise-dissolve']._rows;
        c.save();
        c.beginPath();
        for (var row = 0; row < rowsCount; row++) {
          for (var col = 0; col < colsCount; col++) {
            if (noise[row * colsCount + col] < p) {
              c.rect(col * 8, row * 8, 8, 8);
            }
          }
        }
        c.clip();
        drawCover(c, i2, 0, 0, w, h);
        c.restore();
      },
    },
    displacement: {
      label: 'Displacement',
      render: function(c, i1, i2, p, w, h) {
        drawCover(c, p < 0.5 ? i1 : i2, 0, 0, w, h);
        var sliceH = 4;
        var amp = Math.sin(p * Math.PI) * 20;
        var imgData = c.getImageData(0, 0, w, h);
        c.clearRect(0, 0, w, h);
        c.putImageData(imgData, 0, 0);
        for (var y = 0; y < h; y += sliceH) {
          var offset = Math.sin(y * 0.05 + p * 10) * amp;
          c.drawImage(canvas, 0, y, w, sliceH, offset, y, w, sliceH);
        }
        c.globalAlpha = p;
        drawCover(c, i2, 0, 0, w, h);
        c.globalAlpha = 1;
      },
    },
    ripple: {
      label: 'Ripple',
      render: function(c, i1, i2, p, w, h) {
        drawCover(c, p < 0.5 ? i1 : i2, 0, 0, w, h);
        var amp = Math.sin(p * Math.PI) * 12;
        var sliceH = 3;
        for (var y = 0; y < h; y += sliceH) {
          var dy = y - h / 2;
          var dist = Math.abs(dy) / (h / 2);
          var offset = Math.sin(dist * 20 - p * 15) * amp * (1 - dist);
          c.drawImage(canvas, 0, y, w, sliceH, offset, y, w, sliceH);
        }
        c.globalAlpha = p;
        drawCover(c, i2, 0, 0, w, h);
        c.globalAlpha = 1;
      },
    },
    'rgb-shift': {
      label: 'RGB Shift',
      render: function(c, i1, i2, p, w, h) {
        var shift = Math.sin(p * Math.PI) * 15;
        c.globalAlpha = 1 - p;
        drawCover(c, i1, 0, 0, w, h);
        c.globalAlpha = p;
        drawCover(c, i2, 0, 0, w, h);
        c.globalAlpha = 1;
        c.globalCompositeOperation = 'lighter';
        c.globalAlpha = 0.15;
        c.drawImage(p < 0.5 ? i1 : i2, shift, 0, w, h);
        c.drawImage(p < 0.5 ? i1 : i2, -shift, 0, w, h);
        c.globalCompositeOperation = 'source-over';
        c.globalAlpha = 1;
      },
    },
    'particle-dissolve': {
      label: 'Particle Dissolve',
      render: function(c, i1, i2, p, w, h) {
        drawCover(c, i2, 0, 0, w, h);
        var blockSize = 12;
        var cols = Math.ceil(w / blockSize);
        var rows = Math.ceil(h / blockSize);
        for (var r = 0; r < rows; r++) {
          for (var cl = 0; cl < cols; cl++) {
            var h2 = hash(cl + 0.5, r + 0.5);
            if (h2 > p) {
              c.drawImage(i1, cl * blockSize, r * blockSize, blockSize, blockSize,
                cl * blockSize, r * blockSize, blockSize, blockSize);
            }
          }
        }
      },
    },
  };

  const effectKeys = Object.keys(effects);
  const imgCache = {};

  function loadImg(url, cb) {
    if (imgCache[url]) {
      cb(imgCache[url]);
      return;
    }
    var img = new Image();
    img.onload = function() {
      imgCache[url] = img;
      cb(img);
    };
    img.src = url;
  }

  let currentEffect = 'cross-dissolve';
  let img1 = null;
  let img2 = null;
  let transitioning = false;
  let transitionStart = 0;
  const DURATION = 1200;

  function renderFrame() {
    if (!transitioning || !img1 || !img2) return;
    var elapsed = performance.now() - transitionStart;
    var raw = Math.min(1.0, elapsed / DURATION);
    var p = raw < 0.5 ? 2 * raw * raw : 1 - Math.pow(-2 * raw + 2, 2) / 2;

    var w = canvas.width;
    var h = canvas.height;
    ctx.clearRect(0, 0, w, h);

    var fx = effects[currentEffect];
    if (fx) fx.render(ctx, img1, img2, p, w, h);

    if (raw < 1.0) {
      requestAnimationFrame(renderFrame);
    } else {
      canvas.style.opacity = '0';
      transitioning = false;
    }
  }

  window.triggerTransition = function(fromUrl, toUrl, onCanvasReady) {
    if (currentEffect === 'none') return;
    resize();
    var loaded = 0;
    function check() {
      loaded++;
      if (loaded === 2 && img1 && img2) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        drawCover(ctx, img1, 0, 0, canvas.width, canvas.height);
        canvas.style.opacity = '1';
        if (onCanvasReady) onCanvasReady();
        transitionStart = performance.now();
        transitioning = true;
        requestAnimationFrame(renderFrame);
      }
    }
    loadImg(fromUrl, function(i) { img1 = i; check(); });
    loadImg(toUrl, function(i) { img2 = i; check(); });
  };

  var panel = document.createElement('div');
  panel.id = 'debugPanel';
  panel.innerHTML =
    '<div class="debug-panel-title">Transition FX</div>' +
    '<select id="fxSelect" class="debug-panel-select">' +
    '<option value="none">None (CSS fade)</option>' +
    effectKeys.map(function(k) {
      return '<option value="' + k + '"' + (k === currentEffect ? ' selected' : '') + '>' + effects[k].label + '</option>';
    }).join('') +
    '</select>' +
    '<button id="fxTest" class="debug-panel-button">Test Transition</button>';
  document.body.appendChild(panel);

  document.getElementById('fxSelect').addEventListener('change', function() {
    currentEffect = this.value;
  });

  document.getElementById('fxTest').addEventListener('click', function() {
    if (currentEffect === 'none') return;
    document.getElementById('yachtNavRight').click();
  });

  var panelVisible = false;
  document.addEventListener('keydown', function(e) {
    if (e.key === 'd' || e.key === 'D') {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
      panelVisible = !panelVisible;
      panel.style.display = panelVisible ? 'block' : 'none';
    }
  });
})();
