export default function brandRegistration(role, previousAnswers, questions) {
    return {
        role,
        step: 0,
        answers: Object.fromEntries(Object.keys(questions).map(key => [key, previousAnswers[key] ?? ''])),
        titles: ['Créer un compte', 'Votre marque et son périmètre', 'Matières et circularité', 'Origine animale et alternatives', 'Fabrication et environnement', 'Preuves et objectifs'],
        visible(key) {
            const condition = questions[key].when;
            return !condition || (this.visible(condition[0]) && this.answers[condition[0]] === condition[1]);
        },
        submit(event) {
            const inputs = [...event.target.querySelectorAll('input, select, textarea')];
            const invalid = inputs.find(input => !input.disabled && input.offsetParent !== null && !input.checkValidity());
            if (invalid) {
                event.preventDefault();
                invalid.reportValidity();
                return;
            }
            if (this.role === 'brand' && this.step < 5) {
                event.preventDefault();
                this.step++;
                this.$nextTick(() => event.target.scrollIntoView({ behavior: 'smooth', block: 'start' }));
            }
        },
    };
}
