const SPACE_NAME = 'levihsu/OOTDiffusion';

function frameBlob(video) {
    const canvas = document.createElement('canvas');
    canvas.width = 768;
    canvas.height = 1024;
    const context = canvas.getContext('2d');
    const sourceRatio = video.videoWidth / video.videoHeight;
    const targetRatio = canvas.width / canvas.height;
    let sourceWidth = video.videoWidth;
    let sourceHeight = video.videoHeight;

    if (sourceRatio > targetRatio) sourceWidth = sourceHeight * targetRatio;
    else sourceHeight = sourceWidth / targetRatio;

    const sourceX = (video.videoWidth - sourceWidth) / 2;
    const sourceY = (video.videoHeight - sourceHeight) / 2;
    context.translate(canvas.width, 0);
    context.scale(-1, 1);
    context.drawImage(video, sourceX, sourceY, sourceWidth, sourceHeight, 0, 0, canvas.width, canvas.height);

    return new Promise((resolve, reject) => {
        canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error('Capture impossible')), 'image/jpeg', 0.92);
    });
}

function findOutputUrl(value) {
    if (!value) return null;
    if (typeof value === 'string' && /^https?:\/\//.test(value)) return value;
    if (Array.isArray(value)) {
        for (const item of value) {
            const url = findOutputUrl(item);
            if (url) return url;
        }
    }
    if (typeof value === 'object') {
        if (typeof value.url === 'string') return value.url;
        for (const item of Object.values(value)) {
            const url = findOutputUrl(item);
            if (url) return url;
        }
    }
    return null;
}

function categoryLabel(category) {
    if (category === 'bottom' || category === 'lower') return 'Lower-body';
    if (category === 'dress' || category === 'overall') return 'Dress';
    return 'Upper-body';
}

function fullBodyEndpoint(api) {
    const endpoints = { ...(api.named_endpoints ?? {}), ...(api.unnamed_endpoints ?? {}) };
    const entry = Object.entries(endpoints).find(([, definition]) => {
        const parameters = definition.parameters ?? [];
        return parameters.length === 7 && parameters.some(parameter => /category/i.test(`${parameter.label ?? ''} ${parameter.parameter_name ?? ''}`));
    });
    if (!entry) throw new Error('Le service IA ne publie plus son endpoint d’essayage.');
    return entry[0];
}

export default function virtualTryOn({ imageUrl, category }) {
    return {
        open: false,
        loading: false,
        running: false,
        generating: false,
        error: '',
        status: 'Placez-vous face à la caméra, avec le corps bien visible.',
        stream: null,
        capturedUrl: '',
        resultUrl: '',
        captureFile: null,
        job: null,

        async openTryOn() {
            this.open = true;
            this.error = '';
            await this.$nextTick();
            await this.startCamera();
        },

        async startCamera() {
            this.loading = true;
            this.error = '';
            this.status = 'Activation de la caméra…';
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 960 }, height: { ideal: 1280 } },
                    audio: false,
                });
                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.running = true;
                this.status = 'Prêt · gardez le haut du corps et les bras visibles.';
            } catch (error) {
                console.error('Camera initialization failed:', error);
                this.error = 'Impossible d’ouvrir la caméra. Autorisez son accès dans le navigateur puis réessayez.';
            } finally {
                this.loading = false;
            }
        },

        stopCamera() {
            this.running = false;
            this.stream?.getTracks().forEach(track => track.stop());
            this.stream = null;
            if (this.$refs.video) this.$refs.video.srcObject = null;
        },

        async captureAndGenerate() {
            if (this.generating || (!this.running && !this.captureFile)) return;
            this.error = '';
            this.generating = true;
            this.status = this.captureFile ? 'Nouvelle génération de l’essayage…' : 'Capture de la photo…';
            try {
                if (!this.captureFile) {
                    this.captureFile = await frameBlob(this.$refs.video);
                    this.stopCamera();
                    this.revokeUrl('capturedUrl');
                    this.capturedUrl = URL.createObjectURL(this.captureFile);
                }
                this.status = 'Connexion au service IA gratuit…';

                const { Client, handle_file: handleFile } = await import('@gradio/client');
                const client = await Client.connect(SPACE_NAME, { events: ['status', 'data'] });
                const endpoint = fullBodyEndpoint(await client.view_api());
                const garmentUrl = new URL(imageUrl, window.location.href).href;
                const payload = [
                    handleFile(this.captureFile),
                    handleFile(garmentUrl),
                    categoryLabel(category),
                    1,
                    20,
                    2,
                    -1,
                ];

                this.status = 'Votre demande est dans la file d’attente gratuite…';
                this.job = client.submit(endpoint, payload);
                let generatedUrl = null;

                for await (const message of this.job) {
                    if (message.type === 'status') {
                        if (message.stage === 'generating') this.status = 'L’IA habille votre photo… cela peut prendre une minute.';
                        else if (message.stage === 'pending' && Number.isFinite(message.position)) this.status = `File d’attente gratuite · position ${message.position + 1}.`;
                        else if (message.stage === 'error') throw new Error(message.message || 'Erreur du service IA');
                    }
                    if (message.type === 'data') generatedUrl = findOutputUrl(message.data) ?? generatedUrl;
                }

                if (!generatedUrl) throw new Error('Aucune image n’a été retournée.');
                this.resultUrl = generatedUrl;
                this.status = 'Essayage IA terminé · vous pouvez télécharger le résultat.';
                window.appNotify('Votre essayage réaliste est prêt.');
            } catch (error) {
                console.error('AI virtual try-on failed:', error);
                this.error = 'Le service IA gratuit est occupé ou temporairement indisponible. Réessayez dans quelques minutes.';
                this.status = 'La capture est conservée sur cet écran pour permettre un nouvel essai.';
            } finally {
                this.job = null;
                this.generating = false;
            }
        },

        async downloadResult() {
            if (!this.resultUrl) return;
            try {
                const response = await fetch(this.resultUrl);
                if (!response.ok) throw new Error('Téléchargement impossible');
                const objectUrl = URL.createObjectURL(await response.blob());
                const link = document.createElement('a');
                link.href = objectUrl;
                link.download = `essayage-ia-${Date.now()}.png`;
                link.click();
                setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
            } catch {
                window.open(this.resultUrl, '_blank', 'noopener');
            }
        },

        async takeAnotherPhoto() {
            this.job?.cancel();
            this.job = null;
            this.generating = false;
            this.captureFile = null;
            this.revokeUrl('capturedUrl');
            this.resultUrl = '';
            await this.startCamera();
        },

        revokeUrl(property) {
            if (this[property]?.startsWith('blob:')) URL.revokeObjectURL(this[property]);
            this[property] = '';
        },

        closeTryOn() {
            this.job?.cancel();
            this.job = null;
            this.stopCamera();
            this.captureFile = null;
            this.revokeUrl('capturedUrl');
            this.resultUrl = '';
            this.generating = false;
            this.open = false;
        },
    };
}
