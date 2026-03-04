# Documentation Technique : Logique des Graphiques et Historique DashMed

## Présentation
DashMed implémente un système de surveillance des patients haute performance capable de restituer des ensembles de données historiques massifs (ex: 1 mesure/seconde sur plusieurs jours) sans compromettre la stabilité du serveur ni la réactivité du client.

## 1. Architecture du Flux de Données
Le trajet des données de la base de données jusqu'à l'écran de l'utilisateur suit ce parcours :
1. **Requête** : `chart.js` appelle `/api_history?param=XYZ&limit=0`.
2. **Backend** : `PatientController::apiHistory` traite la requête.
3. **Récupération Optimisée** :
    - Pour les petits jeux de données, les lignes brutes sont extraites.
    - Pour les jeux de données massifs (> 50 000 lignes), une **Pré-agrégation SQL** est appliquée via `GROUP BY` et `AVG()`.
4. **Réduction (Downsampling)** : Le `DownsamplingService` applique l'algorithme **LTTB** via un flux Generator PHP (mémoire O(1)).
5. **Mise en cache** : Le résultat est sauvegardé dans un cache fichier côté serveur (TTL 30s).
6. **Livraison** : Le JSON est envoyé au client.
7. **Cache Client** : `chart.js` stocke le résultat dans un cache en mémoire `historyCache`.
8. **Rendu** : Chart.js affiche les points réduits (5 000 points max).

## 2. Algorithme LTTB (Largest Triangle Three Buckets)
Le LTTB est utilisé pour réduire les données haute résolution en un ensemble représentatif de points tout en préservant les caractéristiques visuelles (pics, creux et tendances).

### Implémentation en Flux (Streaming)
Contrairement aux implémentations standards qui nécessitent tout le jeu de données en mémoire, notre méthode `downsampleLTTBStream` traite les données via un `Iterator`.
- **Complexité Mémoire** : $O(k)$ où $k$ est la taille du bucket.
- **Complexité Temporelle** : $O(n)$ où $n$ est le nombre de points.
- **Précision** : Correspond exactement aux résultats du LTTB basé sur des tableaux.

## 3. Stratégie de Cache Multi-couches

| Couche | Type | TTL | Objectif |
| :--- | :--- | :--- | :--- |
| **Client** | Mémoire JS | Session | Réouverture instantanée des modales et changement rapide de métrique. |
| **Serveur** | Cache Fichier | 30s | Évite les calculs LTTB redondants pour plusieurs observateurs. |
| **Base de données** | Pré-agragg SQL | N/A | Réduit des milliards de points potentiels à quelques milliers avant le traitement PHP. |

## 4. Logique de Pré-agrégation SQL
Lorsque le nombre de lignes dépasse 50 000, nous ne transférons pas toutes les lignes brutes à PHP. À la place :
```sql
SELECT 
    AVG(value) as value,
    FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(`timestamp`) / :interval) * :interval) as `timestamp`
FROM patient_data
GROUP BY FLOOR(UNIX_TIMESTAMP(`timestamp`) / :interval)
```
Cela délègue la première passe de réduction au moteur de base de données, réduisant considérablement les E/S et l'utilisation CPU sur le serveur applicatif.

## 5. Zoom & Navigation Frontend
- **Vue Initiale** : Zoom par défaut sur les 2 dernières minutes pour afficher les données les plus récentes.
- **Navigation Manuelle** : Les utilisateurs peuvent se déplacer (drag) ou zoomer (molette/pincement) sur tout l'historique chargé.
- **Préréglages d'Intervalle** : Des durées fixes (5m, 15m, 1H, 24H, 7J, etc.) ajustent instantanément l'échelle visible du graphique.

## 6. Références du Code
- **Service Back-end** : [DownsamplingService.php](app/services/DownsamplingService.php)
- **Optimisation Repository** : [MonitorRepository.php](app/models/repositories/MonitorRepository.php)
- **Point de terminaison API** : [PatientController.php](app/controllers/PatientController.php#L700)
- **Logique Front-end** : [chart.js](public/assets/js/component/modal/chart.js)
