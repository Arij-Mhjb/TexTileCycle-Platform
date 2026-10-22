let sharedDetector;

async function poseDetector() {
    if (sharedDetector) return sharedDetector;
    const { FilesetResolver, PoseLandmarker } = await import('@mediapipe/tasks-vision');
    const vision = await FilesetResolver.forVisionTasks('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@1.1.1/wasm');
    sharedDetector = await PoseLandmarker.createFromOptions(vision, {
        baseOptions: {
            modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/1/pose_landmarker_lite.task',
            delegate: 'CPU',
        },
        runningMode: 'VIDEO',
        numPoses: 1,
        minPoseDetectionConfidence: 0.35,
        minPosePresenceConfidence: 0.35,
        minTrackingConfidence: 0.35,
        outputSegmentationMasks: false,
    });
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
                this.detector = await poseDetector();
                stage = 'garment';
                this.garment = garmentCutout(await loadImage(imageUrl));
                this.status = 'Filtre AR prêt · placez le haut du corps dans le cadre.';
            } catch (error) {
                console.error('Virtual try-on initialization failed:', error);
                if (stage === 'camera') {
                    this.error = 'Impossible d’ouvrir la caméra. Vérifiez son autorisation dans le navigateur.';
                } else if (stage === 'garment') {
                    this.error = 'Le suivi AR est prêt, mais l’image du vêtement est inaccessible. Vérifiez l’image du produit.';
                } else {
                    this.error = 'La caméra reste en direct, mais le modèle de suivi AR n’a pas pu être chargé. Réessayez.';
                }
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
                const result = this.detector.detectForVideo(video, performance.now());
                const landmarks = result.landmarks?.[0];
                if (landmarks?.length) {
                    const landmarkNames = {
                        nose: 0, left_ear: 7, right_ear: 8,
                        left_shoulder: 11, right_shoulder: 12,
                        left_elbow: 13, right_elbow: 14,
                        left_wrist: 15, right_wrist: 16,
                        left_hip: 23, right_hip: 24,
                        left_knee: 25, right_knee: 26,
                    };
                    const points = Object.entries(landmarkNames).map(([name, index]) => ({
                        name,
                        x: (1 - landmarks[index].x) * video.videoWidth,
                        y: landmarks[index].y * video.videoHeight,
                        score: Math.min(landmarks[index].visibility ?? 1, landmarks[index].presence ?? 1),
                    }));
                    this.updatePose(points, canvas.width, canvas.height, video.videoWidth, video.videoHeight);
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
