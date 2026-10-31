import { useEffect, useRef, useState } from "react";

let THREE;

function makeMaterial(color, roughness = 0.72) {
  return new THREE.MeshStandardMaterial({ color, roughness });
}

function makeSphere(group, material, position, scale) {
  const mesh = new THREE.Mesh(
    new THREE.SphereGeometry(1, 24, 18),
    material,
  );
  mesh.position.set(...position);
  mesh.scale.set(...scale);
  group.add(mesh);
  return mesh;
}

function makeCapsule(group, material, start, end, radius) {
  const from = new THREE.Vector3(...start);
  const to = new THREE.Vector3(...end);
  const direction = to.clone().sub(from);
  const mesh = new THREE.Mesh(
    new THREE.CapsuleGeometry(radius, Math.max(0.01, direction.length()), 4, 10),
    material,
  );
  mesh.position.copy(from.add(to).multiplyScalar(0.5));
  mesh.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), direction.normalize());
  group.add(mesh);
  return mesh;
}

function makeMouth(head, material, points) {
  const curve = new THREE.CatmullRomCurve3(
    points.map(([x, y, z]) => new THREE.Vector3(x, y, z)),
  );
  const mesh = new THREE.Mesh(
    new THREE.TubeGeometry(curve, 16, 0.022, 6, false),
    material,
  );
  head.add(mesh);
  return mesh;
}

function makeApronLabel() {
  const canvas = document.createElement("canvas");
  canvas.width = 256;
  canvas.height = 96;
  const context = canvas.getContext("2d");
  context.font = "700 62px Arial, sans-serif";
  context.textAlign = "center";
  context.textBaseline = "middle";
  context.fillStyle = "#856128";
  context.fillText("AFC", 128, 48);
  const texture = new THREE.CanvasTexture(canvas);
  texture.colorSpace = THREE.SRGBColorSpace;
  return texture;
}

function createCharacter(scene) {
  const materials = {
    skin: makeMaterial("#e9b181"),
    skinLight: makeMaterial("#f3c49e"),
    hair: makeMaterial("#49352c"),
    hairLight: makeMaterial("#725448"),
    shirt: makeMaterial("#856128"),
    apron: makeMaterial("#fffefa"),
    eye: new THREE.MeshStandardMaterial({ color: "#fffaf0", roughness: 0.35 }),
    pupil: makeMaterial("#49352c", 0.4),
    cheek: new THREE.MeshStandardMaterial({
      color: "#d98272",
      roughness: 0.8,
      transparent: true,
      opacity: 0.5,
    }),
    mouth: makeMaterial("#a9574c"),
    shoe: makeMaterial("#49352c"),
  };
  const character = new THREE.Group();
  scene.add(character);

  const floorShadow = new THREE.Mesh(
    new THREE.CircleGeometry(0.72, 32),
    new THREE.MeshBasicMaterial({
      color: "#392b13",
      transparent: true,
      opacity: 0.12,
      depthWrite: false,
    }),
  );
  floorShadow.rotation.x = -Math.PI / 2;
  floorShadow.position.set(0, -1.58, 0);
  floorShadow.scale.set(1, 0.32, 1);
  scene.add(floorShadow);

  makeSphere(character, materials.shirt, [0, -0.46, 0], [0.51, 0.74, 0.4]);
  makeSphere(character, materials.skin, [0, 0.22, 0], [0.2, 0.25, 0.21]);

  const apron = new THREE.Mesh(
    new THREE.BoxGeometry(0.64, 0.74, 0.08),
    materials.apron,
  );
  apron.position.set(0, -0.56, 0.385);
  character.add(apron);
  makeCapsule(character, materials.apron, [-0.3, -0.12, 0.36], [-0.18, 0.08, 0.34], 0.055);
  makeCapsule(character, materials.apron, [0.3, -0.12, 0.36], [0.18, 0.08, 0.34], 0.055);

  const label = new THREE.Mesh(
    new THREE.PlaneGeometry(0.32, 0.12),
    new THREE.MeshBasicMaterial({
      map: makeApronLabel(),
      transparent: true,
      depthWrite: false,
    }),
  );
  label.position.set(0, -0.73, 0.432);
  character.add(label);

  const legs = [];
  for (const side of [-1, 1]) {
    const leg = new THREE.Group();
    leg.position.set(side * 0.21, -1.02, 0);
    character.add(leg);
    makeCapsule(leg, materials.skin, [0, 0, 0], [0, -0.38, 0], 0.095);
    makeSphere(leg, materials.shoe, [0, -0.4, 0.09], [0.16, 0.085, 0.22]);
    legs.push(leg);
  }

  const pointingArm = new THREE.Group();
  pointingArm.position.set(-0.4, -0.2, 0);
  character.add(pointingArm);
  makeCapsule(pointingArm, materials.shirt, [0, 0, 0], [-0.33, 0.13, 0.02], 0.15);
  makeCapsule(pointingArm, materials.skin, [-0.32, 0.13, 0.02], [-0.67, 0.28, 0.1], 0.105);
  makeSphere(pointingArm, materials.skin, [-0.69, 0.29, 0.1], [0.13, 0.12, 0.12]);
  makeCapsule(pointingArm, materials.skin, [-0.73, 0.32, 0.1], [-0.98, 0.4, 0.12], 0.045);
  makeCapsule(pointingArm, materials.skin, [-0.69, 0.24, 0.1], [-0.82, 0.16, 0.12], 0.04);
  makeCapsule(pointingArm, materials.skin, [-0.64, 0.23, 0.1], [-0.74, 0.14, 0.12], 0.04);

  makeCapsule(character, materials.shirt, [0.4, -0.2, 0], [0.63, -0.47, 0.04], 0.14);
  makeCapsule(character, materials.skin, [0.63, -0.47, 0.04], [0.72, -0.63, 0.11], 0.09);

  const head = new THREE.Group();
  head.position.set(0, 0.72, 0.02);
  character.add(head);
  makeSphere(head, materials.skin, [0, 0, 0], [0.56, 0.65, 0.47]);
  makeSphere(head, materials.hair, [0, 0.42, 0], [0.57, 0.31, 0.48]);
  makeSphere(head, materials.hairLight, [-0.2, 0.48, 0.32], [0.2, 0.09, 0.08]);
  makeSphere(head, materials.hair, [0.15, 0.38, 0.41], [0.18, 0.13, 0.09]);

  for (const side of [-1, 1]) {
    makeSphere(head, materials.skin, [side * 0.53, -0.06, 0.02], [0.11, 0.16, 0.12]);
  }

  const eyes = [];
  for (const side of [-1, 1]) {
    const eye = new THREE.Group();
    eye.position.set(side * 0.2, 0.04, 0.405);
    head.add(eye);
    makeSphere(eye, materials.eye, [0, 0, 0], [0.115, 0.145, 0.065]);
    const pupil = makeSphere(eye, materials.pupil, [0.015, 0, 0.055], [0.052, 0.073, 0.035]);
    makeSphere(eye, materials.eye, [0.028, 0.03, 0.085], [0.018, 0.023, 0.012]);
    eyes.push({ eye, pupil, side });

    makeCapsule(head, materials.hair, [side * 0.31, 0.23, 0.39], [side * 0.12, 0.27, 0.43], 0.035);
    makeSphere(head, materials.cheek, [side * 0.37, -0.17, 0.35], [0.09, 0.055, 0.035]);
  }

  makeSphere(head, materials.skinLight, [0, -0.11, 0.47], [0.09, 0.1, 0.09]);
  const smile = makeMouth(head, materials.mouth, [
    [-0.15, -0.25, 0.43],
    [0, -0.33, 0.46],
    [0.15, -0.25, 0.43],
  ]);
  const neutral = makeMouth(head, materials.mouth, [
    [-0.12, -0.29, 0.44],
    [0, -0.3, 0.46],
    [0.12, -0.29, 0.44],
  ]);
  const tired = makeMouth(head, materials.mouth, [
    [-0.13, -0.31, 0.44],
    [0, -0.24, 0.46],
    [0.13, -0.31, 0.44],
  ]);
  neutral.visible = false;
  tired.visible = false;

  const hatBand = new THREE.Mesh(
    new THREE.CylinderGeometry(0.36, 0.39, 0.14, 24),
    materials.apron,
  );
  hatBand.position.y = 0.66;
  head.add(hatBand);
  makeSphere(head, materials.apron, [-0.2, 0.8, 0], [0.25, 0.26, 0.26]);
  makeSphere(head, materials.apron, [0.1, 0.88, 0], [0.29, 0.32, 0.28]);
  makeSphere(head, materials.apron, [0.34, 0.76, 0], [0.2, 0.22, 0.23]);

  return {
    character,
    eyes,
    floorShadow,
    head,
    legs,
    mouths: { happy: smile, idle: neutral, tired },
    pointingArm,
  };
}

export default function FloatingMascot({ mood }) {
  const containerRef = useRef(null);
  const moodRef = useRef(mood);
  const [renderError, setRenderError] = useState(false);

  useEffect(() => {
    moodRef.current = mood;
  }, [mood]);

  useEffect(() => {
    const container = containerRef.current;
    if (!container) return undefined;

    let renderer;
    let scene;
    let resizeObserver;
    let reducedMotionQuery;
    let onMotionPreferenceChange;
    let frameId;
    let cancelled = false;

    const cleanup = () => {
      if (frameId) window.cancelAnimationFrame(frameId);
      if (reducedMotionQuery && onMotionPreferenceChange) {
        reducedMotionQuery.removeEventListener("change", onMotionPreferenceChange);
      }
      resizeObserver?.disconnect();
      const geometries = new Set();
      const materials = new Set();
      const textures = new Set();
      scene?.traverse((object) => {
        if (object.geometry) geometries.add(object.geometry);
        const objectMaterials = Array.isArray(object.material)
          ? object.material
          : object.material
            ? [object.material]
            : [];
        objectMaterials.forEach((material) => {
          materials.add(material);
          if (material.map) textures.add(material.map);
        });
      });
      geometries.forEach((geometry) => geometry.dispose());
      materials.forEach((material) => material.dispose());
      textures.forEach((texture) => texture.dispose());
      renderer?.dispose();
      if (renderer?.domElement.parentNode === container) {
        container.removeChild(renderer.domElement);
      }
    };

    const initialize = (threeModule) => {
      THREE = threeModule;
      renderer = new THREE.WebGLRenderer({
        alpha: true,
        antialias: true,
        powerPreference: "low-power",
      });

      scene = new THREE.Scene();
      const camera = new THREE.PerspectiveCamera(33, 1, 0.1, 30);
      camera.position.set(0, 0.12, 7.6);
      camera.lookAt(0, 0.12, 0);
      scene.add(new THREE.HemisphereLight("#fff5db", "#60492c", 2.1));

      const keyLight = new THREE.DirectionalLight("#fff8e9", 3);
      keyLight.position.set(-3, 5, 6);
      scene.add(keyLight);
      const fillLight = new THREE.DirectionalLight("#e7ad61", 1.1);
      fillLight.position.set(4, 1, 3);
      scene.add(fillLight);

      const character = createCharacter(scene);
      renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
      renderer.setSize(container.clientWidth, container.clientHeight, false);
      renderer.outputColorSpace = THREE.SRGBColorSpace;
      renderer.setClearColor(0x000000, 0);
      renderer.domElement.setAttribute("aria-hidden", "true");
      container.appendChild(renderer.domElement);

      reducedMotionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
      let isReducedMotion = reducedMotionQuery.matches;
      const clock = new THREE.Clock();

      const resize = () => {
        const width = container.clientWidth;
        const height = container.clientHeight;
        if (!width || !height) return;
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
        renderer.render(scene, camera);
      };

      const render = () => {
        frameId = window.requestAnimationFrame(render);
        const time = clock.getElapsedTime();
        const currentMood = moodRef.current;
        const animatedTime = isReducedMotion ? 0 : time;

        character.character.position.y = Math.sin(animatedTime * 2.2) * (
          currentMood === "tired" ? 0.015 : currentMood === "happy" ? 0.055 : 0.035
        );
        character.character.rotation.y = Math.sin(animatedTime * 0.65) * 0.07;
        character.head.rotation.y = Math.sin(animatedTime * 0.9) * 0.12;
        character.head.rotation.z = currentMood === "tired"
          ? -0.1
          : currentMood === "happy"
            ? Math.sin(animatedTime * 8) * 0.045
            : 0;
        character.pointingArm.rotation.z = currentMood === "happy"
          ? Math.sin(animatedTime * 8) * 0.06
          : Math.sin(animatedTime * 1.8) * 0.025;
        character.legs.forEach((leg, index) => {
          leg.rotation.z = currentMood === "tired"
            ? Math.sin(animatedTime * 0.9) * 0.015
            : Math.sin(animatedTime * 3 + index * Math.PI) * 0.07;
        });
        character.eyes.forEach(({ eye, pupil, side }) => {
          eye.scale.y = currentMood === "tired"
            ? 0.58
            : isReducedMotion
              ? 1
              : Math.max(0.12, 1 - Math.max(0, Math.sin(time * 1.15 + side) ** 36) * 0.88);
          pupil.position.x = side * 0.015 + Math.sin(animatedTime * 0.9) * 0.018;
        });

        character.mouths.happy.visible = currentMood === "happy";
        character.mouths.idle.visible = currentMood === "idle";
        character.mouths.tired.visible = currentMood === "tired";
        character.floorShadow.material.opacity = currentMood === "happy" ? 0.09 : 0.12;
        renderer.render(scene, camera);
      };

      onMotionPreferenceChange = (event) => {
        isReducedMotion = event.matches;
      };
      reducedMotionQuery.addEventListener("change", onMotionPreferenceChange);
      resizeObserver = new ResizeObserver(resize);
      resizeObserver.observe(container);
      resize();
      render();
    };

    import("three")
      .then((threeModule) => {
        if (cancelled) return;
        initialize(threeModule);
      })
      .catch((error) => {
        console.error("The 3D AFC mascot could not be initialized.", error);
        if (!cancelled) setRenderError(true);
        cleanup();
      });

    return () => {
      cancelled = true;
      cleanup();
    };
  }, []);

  return (
    <span
      ref={containerRef}
      className={`floating-mascot-figure mascot-${mood}${renderError ? " mascot-3d-error" : ""}`}
      aria-hidden="true"
    >
      {renderError && <span className="mascot-3d-error-message">3D character unavailable</span>}
    </span>
  );
}
