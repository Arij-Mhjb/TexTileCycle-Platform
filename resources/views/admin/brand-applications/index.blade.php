<x-app-layout>
<x-ui.page-heading title="Inscriptions marques" description="Questionnaires, recommandations IA et activation des comptes." />
<div class="mt-6 overflow-x-auto rounded-2xl border bg-white dark:border-slate-800 dark:bg-slate-900">
<table class="w-full text-left text-sm">
<thead class="bg-slate-50 dark:bg-slate-800"><tr><th class="p-4">Marque</th><th class="p-4">Réception</th><th class="p-4">Analyse IA</th><th class="p-4">Compte</th><th class="p-4">Dossier</th></tr></thead>
<tbody>@forelse($applications as $application)
<tr class="border-t dark:border-slate-800"><td class="p-4"><strong>{{ $application->brand->name }}</strong><p class="text-slate-500">{{ $application->brand->user->email }}</p></td><td class="p-4">{{ $application->created_at->format('d/m/Y H:i') }}</td><td class="p-4">{{ ['pending'=>'Programmée', 'completed'=>'Terminée', 'unavailable'=>'Non configurée', 'failed'=>'Échec'][$application->analysis_status] ?? $application->analysis_status }}</td><td class="p-4">{{ $application->status === 'approved' ? 'Activé' : 'À examiner' }}</td><td class="p-4"><a class="font-bold text-emerald-700" href="{{ route('admin.brand-applications.show', $application) }}">Examiner →</a></td></tr>
@empty<tr><td colspan="5" class="p-8 text-center text-slate-500">Aucune inscription marque à examiner.</td></tr>@endforelse
</tbody></table>
</div><div class="mt-5">{{ $applications->links() }}</div>
</x-app-layout>
