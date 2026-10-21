import { FilesetResolver, PoseLandmarker } from '@mediapipe/tasks-vision';

const WASM_PATH = '/vendor/mediapipe';
const MODEL_PATH = 'https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/latest/pose_landmarker_lite.task';
let sharedLandmarker;

async function landmarker() {
    if (sharedLandmarker) return sharedLandmarker;
    const vision = await FilesetResolver.forVisionTasks(WASM_PATH);
    const options = {
        // IMAGE mode is deliberately used with sampled camera frames. It avoids the
        // timestamp/graph failures triggered by VIDEO mode on some browsers while
        // still providing a fluid preview on the lightweight pose model.
        baseOptions: { modelAssetPath: MODEL_PATH }, runningMode: 'IMAGE', numPoses: 1,
        minPoseDetectionConfidence: 0.55, minPosePresenceConfidence: 0.55, minTrackingConfidence: 0.55,
    };
    sharedLandmarker = await PoseLandmarker.createFromOptions(vision, options);
    return sharedLandmarker;
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
        status: 'Placez-vous face à la caméra, corps visible.',
        scale: 1,
        vertical: 0,
        opacity: 0.9,
        stream: null,
        animation: null,
        detector: null,
        garment: null,
        lastInference: 0,
        smoothed: null,

        async openTryOn() {
            this.open = true;
            this.error = '';
            this.loading = true;
            await this.$nextTick();
            let stage = 'model';
            try {
                [this.detector, this.garment] = await Promise.all([landmarker(), loadImage(imageUrl).then(garmentCutout)]);
                stage = 'camera';
                this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 960 }, height: { ideal: 1280 } }, audio: false });
                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.resize();
                this.running = true;
                this.render();
            } catch (error) {
                console.error('Virtual try-on initialization failed:', error);
                this.error = stage === 'model'
                    ? 'Le modèle IA n’a pas pu être chargé. Rechargez la page puis réessayez.'
                    : 'Impossible d’ouvrir la caméra. Vérifiez son autorisation dans le navigateur.';
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
            context.save();
            context.translate(canvas.width, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            context.restore();

            const now = performance.now();
            if (video.readyState >= 2 && now - this.lastInference > 90) {
                this.lastInference = now;
                try {
                    const result = this.detector.detect(video);
                    if (result.landmarks?.[0]) this.updatePose(result.landmarks[0], canvas.width, canvas.height);
                } catch (error) {
                    console.error('Virtual try-on inference failed:', error);
                    this.error = 'Le moteur IA a rencontré une erreur pendant l’analyse. Fermez puis relancez l’essayage.';
                    this.running = false;
                    return;
                }
            }
            if (this.smoothed) this.drawGarment(context);
            this.animation = requestAnimationFrame(() => this.render());
        },

        updatePose(points, width, height) {
            const point = (index) => ({ x: (1 - points[index].x) * width, y: points[index].y * height, visibility: points[index].visibility ?? 1 });
            const pose = { leftShoulder: point(11), rightShoulder: point(12), leftHip: point(23), rightHip: point(24), leftKnee: point(25), rightKnee: point(26) };
            if ([pose.leftShoulder, pose.rightShoulder, pose.leftHip, pose.rightHip].some(item => item.visibility < 0.45)) {
                this.status = 'Reculez légèrement pour rendre les épaules et les hanches visibles.';
                return;
            }
            const ease = 0.28;
            if (!this.smoothed) this.smoothed = pose;
            else Object.keys(pose).forEach(key => {
                this.smoothed[key].x += (pose[key].x - this.smoothed[key].x) * ease;
                this.smoothed[key].y += (pose[key].y - this.smoothed[key].y) * ease;
            });
            this.status = 'Silhouette détectée · le vêtement suit vos mouvements.';
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

        capture() {
            const link = document.createElement('a');
            link.download = `essayage-virtuel-${Date.now()}.png`;
            link.href = this.$refs.canvas.toDataURL('image/png');
            link.click();
            window.appNotify('Votre essayage a été enregistré sur cet appareil.');
        },

        closeTryOn() {
            this.running = false;
            cancelAnimationFrame(this.animation);
            this.stream?.getTracks().forEach(track => track.stop());
            this.stream = null;
            this.smoothed = null;
            this.open = false;
        },
    };
}
