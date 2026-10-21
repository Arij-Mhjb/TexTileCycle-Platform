import moveNetManifestUrl from '../models/movenet-lightning.json?url';
import moveNetWeightsUrl from '../models/movenet-lightning.bin?url';

let sharedDetector;

async function bundledMoveNetModel() {
    const [manifestResponse, weightsResponse] = await Promise.all([
        fetch(moveNetManifestUrl),
        fetch(moveNetWeightsUrl),
    ]);
    if (!manifestResponse.ok || !weightsResponse.ok) {
        throw new Error(`MoveNet assets unavailable (${manifestResponse.status}/${weightsResponse.status})`);
    }
    const manifest = await manifestResponse.json();
    const weightData = await weightsResponse.arrayBuffer();
    return {
        load: async () => ({
            modelTopology: manifest.modelTopology,
            format: manifest.format,
            generatedBy: manifest.generatedBy,
            convertedBy: manifest.convertedBy,
            weightSpecs: manifest.weightsManifest.flatMap(group => group.weights),
            weightData,
        }),
    };
}

async function poseDetector() {
    if (sharedDetector) return sharedDetector;
    const [tf, poseDetection] = await Promise.all([
        import('@tensorflow/tfjs-core'),
        import('@tensorflow-models/pose-detection'),
        import('@tensorflow/tfjs-backend-webgl'),
        import('@tensorflow/tfjs-backend-cpu'),
    ]);
    const webglReady = await tf.setBackend('webgl');
    if (!webglReady) await tf.setBackend('cpu');
    await tf.ready();
    const modelUrl = await bundledMoveNetModel();
    const options = {
        modelType: poseDetection.movenet.modelType.SINGLEPOSE_LIGHTNING,
        modelUrl,
        enableSmoothing: true,
        minPoseScore: 0.2,
    };
    try {
        sharedDetector = await poseDetection.createDetector(poseDetection.SupportedModels.MoveNet, options);
    } catch (error) {
        if (tf.getBackend() !== 'webgl') throw error;
        console.warn('MoveNet WebGL initialization failed, retrying on CPU:', error);
        await tf.setBackend('cpu');
        await tf.ready();
        sharedDetector = await poseDetection.createDetector(poseDetection.SupportedModels.MoveNet, options);
    }
    return sharedDetector;
}

function loadImage(url) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = url;
    });
}

function garmentCutout(image) {
    const canvas = document.createElement('canvas');
    const longest = Math.max(image.naturalWidth, image.naturalHeight);
    const ratio = Math.min(1, 900 / longest);
    canvas.width = Math.round(image.naturalWidth * ratio);
    canvas.height = Math.round(image.naturalHeight * ratio);
    const context = canvas.getContext('2d', { willReadFrequently: true });
    context.drawImage(image, 0, 0, canvas.width, canvas.height);
    const pixels = context.getImageData(0, 0, canvas.width, canvas.height);
    const sample = (x, y) => {
        const index = (y * canvas.width + x) * 4;
        return [pixels.data[index], pixels.data[index + 1], pixels.data[index + 2]];
    };
    const corners = [sample(0, 0), sample(canvas.width - 1, 0), sample(0, canvas.height - 1), sample(canvas.width - 1, canvas.height - 1)];
    const background = corners.reduce((acc, color) => acc.map((value, index) => value + color[index] / corners.length), [0, 0, 0]);
    const total = canvas.width * canvas.height;
    const visited = new Uint8Array(total);
    const queue = new Int32Array(total);
    let head = 0, tail = 0;
    const enqueue = (x, y) => { const offset = y * canvas.width + x; if (!visited[offset]) { visited[offset] = 1; queue[tail++] = offset; } };
    for (let x = 0; x < canvas.width; x += 1) { enqueue(x, 0); enqueue(x, canvas.height - 1); }
    for (let y = 1; y < canvas.height - 1; y += 1) { enqueue(0, y); enqueue(canvas.width - 1, y); }
    while (head < tail) {
        const offset = queue[head++], x = offset % canvas.width, y = Math.floor(offset / canvas.width), index = offset * 4;
        const distance = Math.hypot(pixels.data[index] - background[0], pixels.data[index + 1] - background[1], pixels.data[index + 2] - background[2]);
        if (distance > 58) continue;
        pixels.data[index + 3] = distance < 35 ? 0 : Math.round(255 * ((distance - 35) / 23));
        if (x > 0) enqueue(x - 1, y); if (x + 1 < canvas.width) enqueue(x + 1, y);
        if (y > 0) enqueue(x, y - 1); if (y + 1 < canvas.height) enqueue(x, y + 1);
    }
    context.putImageData(pixels, 0, 0);
    return canvas;
}

export default function virtualTryOn({ imageUrl, category }) {
    return {
        open: false,
        loading: false,
        running: false,
        error: '',
        status: 'Effet AR prêt · placez-vous face à la caméra.',
        scale: 1,
        vertical: 0,
        opacity: 0.9,
        stream: null,
        animation: null,
        detector: null,
        garment: null,
        lastInference: 0,
        inferencePending: false,
        inferenceFailures: 0,
        smoothed: null,

        async openTryOn() {
            this.open = true;
            this.error = '';
            this.loading = true;
            await this.$nextTick();
            let stage = 'camera';
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 960 }, height: { ideal: 1280 } }, audio: false });
                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.resize();
                this.running = true;
                this.render();
                this.status = 'Caméra en direct · chargement du filtre AR…';
                stage = 'model';
                [this.detector, this.garment] = await Promise.all([poseDetector(), loadImage(imageUrl).then(garmentCutout)]);
                this.status = 'Filtre AR prêt · placez le haut du corps dans le cadre.';
            } catch (error) {
                console.error('Virtual try-on initialization failed:', error);
                this.error = stage === 'camera'
                    ? 'Impossible d’ouvrir la caméra. Vérifiez son autorisation dans le navigateur.'
                    : 'La caméra reste en direct, mais le filtre AR n’a pas pu être chargé. Réessayez.';
            } finally {
                this.loading = false;
            }
        },

        resize() {
            const video = this.$refs.video;
            this.$refs.canvas.width = video.videoWidth || 720;
            this.$refs.canvas.height = video.videoHeight || 960;
        },

        render() {
            if (!this.running) return;
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            const context = canvas.getContext('2d');
            context.clearRect(0, 0, canvas.width, canvas.height);
            const now = performance.now();
            if (this.detector && video.readyState >= 2 && !this.inferencePending && this.inferenceFailures < 3 && now - this.lastInference > 70) {
                this.lastInference = now;
                this.detectPose(video, canvas);
            }
            if (this.smoothed && this.garment) {
                this.drawGarment(context);
                this.drawForeground(context, video);
            }
            this.animation = requestAnimationFrame(() => this.render());
        },

        async detectPose(video, canvas) {
            this.inferencePending = true;
            try {
                const poses = await this.detector.estimatePoses(video, { maxPoses: 1, flipHorizontal: true });
                if (poses[0]?.keypoints?.length) {
                    this.updatePose(poses[0].keypoints, canvas.width, canvas.height, video.videoWidth, video.videoHeight);
                    this.inferenceFailures = 0;
                    this.error = '';
                }
            } catch (error) {
                this.inferenceFailures += 1;
                console.error('Virtual try-on inference failed:', error);
                if (this.inferenceFailures >= 3) this.error = 'La caméra reste en direct, mais le suivi AR est indisponible. Cliquez sur Réessayer.';
            } finally {
                this.inferencePending = false;
            }
        },

        updatePose(points, width, height, sourceWidth, sourceHeight) {
            const byName = Object.fromEntries(points.map(item => [item.name, item]));
            const point = (name) => {
                const item = byName[name] ?? { x: 0, y: 0, score: 0 };
                return { x: item.x * width / sourceWidth, y: item.y * height / sourceHeight, visibility: item.score ?? 0 };
            };
            const pose = {
                nose: point('nose'), leftEar: point('left_ear'), rightEar: point('right_ear'),
                leftShoulder: point('left_shoulder'), rightShoulder: point('right_shoulder'),
                leftElbow: point('left_elbow'), rightElbow: point('right_elbow'),
                leftWrist: point('left_wrist'), rightWrist: point('right_wrist'),
                leftHip: point('left_hip'), rightHip: point('right_hip'),
                leftKnee: point('left_knee'), rightKnee: point('right_knee'),
            };
            if ([pose.leftShoulder, pose.rightShoulder, pose.leftHip, pose.rightHip].some(item => item.visibility < 0.3)) {
                this.status = 'Reculez légèrement pour rendre les épaules et les hanches visibles.';
                return;
            }
            const ease = 0.28;
            if (!this.smoothed) this.smoothed = pose;
            else Object.keys(pose).forEach(key => {
                this.smoothed[key].x += (pose[key].x - this.smoothed[key].x) * ease;
                this.smoothed[key].y += (pose[key].y - this.smoothed[key].y) * ease;
            });
            this.status = 'Effet AR actif · le vêtement suit vos mouvements en direct.';
        },

        drawGarment(context) {
            const pose = this.smoothed;
            const middle = (a, b) => ({ x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 });
            const distance = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
            const shoulders = middle(pose.leftShoulder, pose.rightShoulder);
            const hips = middle(pose.leftHip, pose.rightHip);
            const shoulderWidth = distance(pose.leftShoulder, pose.rightShoulder);
            const hipWidth = distance(pose.leftHip, pose.rightHip);
            const torso = Math.max(80, distance(shoulders, hips));
            let center = { ...shoulders }, drawWidth = shoulderWidth * 1.58, drawHeight = torso * 1.55;

            if (category === 'bottom') {
                center = { x: hips.x, y: hips.y + torso * 0.76 };
                drawWidth = Math.max(hipWidth * 1.65, shoulderWidth * 1.25);
                drawHeight = torso * 2.15;
            } else if (category === 'dress') {
                center = { x: (shoulders.x + hips.x) / 2, y: shoulders.y + torso * 1.28 };
                drawWidth = Math.max(shoulderWidth * 1.62, hipWidth * 1.55);
                const knees = middle(pose.leftKnee, pose.rightKnee);
                drawHeight = Math.max(torso * 2.65, distance(shoulders, knees) * 1.12);
            } else {
                center = { x: (shoulders.x + hips.x) / 2, y: shoulders.y + torso * 0.5 };
            }

            drawWidth *= Number(this.scale);
            drawHeight *= Number(this.scale);
            center.y += Number(this.vertical) * torso;
            const angle = Math.atan2(pose.leftShoulder.y - pose.rightShoulder.y, pose.leftShoulder.x - pose.rightShoulder.x);
            context.save();
            context.translate(center.x, center.y);
            context.rotate(angle);
            context.globalAlpha = Number(this.opacity);
            context.filter = 'drop-shadow(0 8px 10px rgba(15,23,42,.16))';
            const slices = 48;
            for (let slice = 0; slice < slices; slice += 1) {
                const progress = slice / (slices - 1);
                let widthFactor = 1 - progress * 0.1;
                if (category === 'dress') widthFactor = progress < 0.38 ? 0.78 - progress * 0.32 : 0.66 + (progress - 0.38) * 0.68;
                if (category === 'bottom') widthFactor = 1 - progress * 0.2;
                const sourceY = this.garment.height * progress;
                const sourceHeight = this.garment.height / slices + 1;
                const destinationY = -drawHeight / 2 + drawHeight * progress;
                const destinationHeight = drawHeight / slices + 1.5;
                const sliceWidth = drawWidth * widthFactor;
                context.drawImage(this.garment, 0, sourceY, this.garment.width, sourceHeight, -sliceWidth / 2, destinationY, sliceWidth, destinationHeight);
            }
            context.restore();
        },

        drawForeground(context, video) {
            const pose = this.smoothed;
            const shoulderWidth = Math.hypot(pose.leftShoulder.x - pose.rightShoulder.x, pose.leftShoulder.y - pose.rightShoulder.y);
            const armRadius = Math.max(12, shoulderWidth * 0.13);
            const addCapsule = (from, to, radius) => {
                if (!from || !to || from.visibility < 0.35 || to.visibility < 0.35) return;
                const angle = Math.atan2(to.y - from.y, to.x - from.x);
                const px = Math.sin(angle) * radius;
                const py = -Math.cos(angle) * radius;
                context.moveTo(from.x + px, from.y + py);
                context.lineTo(to.x + px, to.y + py);
                context.lineTo(to.x - px, to.y - py);
                context.lineTo(from.x - px, from.y - py);
                context.closePath();
                context.moveTo(from.x + radius, from.y);
                context.arc(from.x, from.y, radius, 0, Math.PI * 2);
                context.moveTo(to.x + radius, to.y);
                context.arc(to.x, to.y, radius, 0, Math.PI * 2);
            };

            context.save();
            context.beginPath();
            addCapsule(pose.leftShoulder, pose.leftElbow, armRadius);
            addCapsule(pose.leftElbow, pose.leftWrist, armRadius * 0.82);
            addCapsule(pose.rightShoulder, pose.rightElbow, armRadius);
            addCapsule(pose.rightElbow, pose.rightWrist, armRadius * 0.82);
            if (pose.nose?.visibility > 0.35) {
                const headWidth = Math.max(shoulderWidth * 0.42, Math.abs((pose.leftEar?.x ?? pose.nose.x) - (pose.rightEar?.x ?? pose.nose.x)) * 1.45);
                context.moveTo(pose.nose.x + headWidth / 2, pose.nose.y);
                context.ellipse(pose.nose.x, pose.nose.y, headWidth / 2, headWidth * 0.68, 0, 0, Math.PI * 2);
            }
            context.clip();
            context.translate(context.canvas.width, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0, context.canvas.width, context.canvas.height);
            context.restore();
        },

        capture() {
            const snapshot = document.createElement('canvas');
            snapshot.width = this.$refs.canvas.width;
            snapshot.height = this.$refs.canvas.height;
            const context = snapshot.getContext('2d');
            context.save();
            context.translate(snapshot.width, 0);
            context.scale(-1, 1);
            context.drawImage(this.$refs.video, 0, 0, snapshot.width, snapshot.height);
            context.restore();
            context.drawImage(this.$refs.canvas, 0, 0);
            const link = document.createElement('a');
            link.download = `essayage-virtuel-${Date.now()}.png`;
            link.href = snapshot.toDataURL('image/png');
            link.click();
            window.appNotify('Votre essayage a été enregistré sur cet appareil.');
        },

        closeTryOn() {
            this.running = false;
            cancelAnimationFrame(this.animation);
            this.stream?.getTracks().forEach(track => track.stop());
            this.stream = null;
            this.inferencePending = false;
            this.inferenceFailures = 0;
            this.smoothed = null;
            this.open = false;
        },
    };
}
