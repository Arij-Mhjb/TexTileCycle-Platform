<?php

namespace Database\Seeders;

use App\Models\Label;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LabelSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['Global Organic Textile Standard (GOTS)','environment','Global Standard gGmbH','Référentiel externe relatif aux fibres biologiques et à des critères environnementaux et sociaux.','https://global-standard.org/'],
            ['OEKO-TEX STANDARD 100','environment','OEKO-TEX','Référentiel externe portant sur le contrôle de substances potentiellement nocives.','https://www.oeko-tex.com/'],
            ['Global Recycled Standard (GRS)','circularity','Textile Exchange','Référentiel externe relatif au contenu recyclé et à la chaîne de contrôle.','https://textileexchange.org/'],
            ['Responsible Wool Standard (RWS)','animals','Textile Exchange','Référentiel externe relatif au bien-être animal et à la traçabilité de la laine.','https://textileexchange.org/'],
            ['Fairtrade Textile Standard','social','Fairtrade International','Référentiel externe portant notamment sur les conditions de travail de la filière textile.','https://www.fairtrade.net/'],
            ['Cradle to Cradle Certified','circularity','Cradle to Cradle Products Innovation Institute','Référentiel externe d’évaluation de la circularité et de la conception des produits.','https://c2ccertified.org/'],
        ];
        $steps = [
            ['Périmètre et engagement','Définir les produits, sites et responsabilités concernés.'],
            ['Traçabilité documentaire','Réunir fournisseurs, factures, compositions et chaîne de contrôle.'],
            ['Pratiques et mesures','Documenter les procédures et indicateurs correspondant au référentiel.'],
            ['Revue des preuves','Vérifier la cohérence et la validité des pièces avant soumission.'],
        ];
        foreach ($definitions as [$name,$pillar,$organization,$description,$url]) {
            $label=Label::updateOrCreate(['slug'=>Str::slug($name)],[
                'name'=>$name,'pillar'=>$pillar,'organization'=>$organization,'description'=>$description,
                'official_url'=>$url,'cost_hint'=>'Tarification communiquée par l’organisme officiel','is_active'=>true,
            ]);
            foreach($steps as $position=>[$title,$stepDescription]) {
                $label->steps()->updateOrCreate(['position'=>$position+1],['title'=>$title,'description'=>$stepDescription]);
            }
        }
    }
}
