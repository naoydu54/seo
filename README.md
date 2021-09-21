IpSeoBundle
==================

Bundle permettant d'intégrer l'outil de personnalisation de pages dans un site web

## Installation

### Etape 1

Pour installer ajouter ces lignes dans le fichier composer.json

```json
{
  "repositories": [{
    "type": "composer",
    "url": "https://www.repo.info-plus.fr/"
  }]
}
```

```json
{
    "require": {
        "ip/seobundle" : "^1.0"
    }
}
```

```json
{
    "config": {
        "component-dir": "web/assets"
    }
}
```

Mettre à jour les vendors

```bash
composer update
```

Et activer le bundle

```php
<?php

// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Ip\SeoBundle\IpSeoBundle(),
    // ...
);
```

### Etape 2

Aucune configuration requise

## Utilisation basique

Lors de la création d'un formulaire ajouter le champ : 

``` php
<?php

use Ip\SeoBundle\Form\Type\IpPageType;

public function buildForm(FormBuilder $builder, array $options)
{
    // ...
    $builder->add('page', IpMetaDescType::class);
    // ...
}
```
