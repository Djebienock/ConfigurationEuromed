# ConfigurationEuromed
Dans ce prpjet, je vais l'architecture complet d'euromed. Dans cette architecture, nous avons 3 vlans.
### Le premiers vlan permet la communication entre les etudiants.
### le deuxieme permet la communication entre prof et prof
### le troisieme permet la communication entre admin et admin.
---
### J'ai configure le ACL pour permettre la communication entre admin et prof et coupe toutes communication entre prof et etudiant ainsi que admin et etudiant
---
### Exemple COMPLET sur un routeur d’étage
interface g0/0
 no shutdown

interface g0/0.10
 encapsulation dot1Q 10
 ip address 10.10.0.1 255.255.255.0

interface g0/0.20
 encapsulation dot1Q 20
 ip address 10.20.0.1 255.255.255.0

interface g0/0.30
 encapsulation dot1Q 30
 ip address 10.30.0.1 255.255.255.0
---
## ADRESSAGE INTER-BÂTIMENTS
| Lien    | Réseau /30     |
| ------- | -------------- |
| B1 ↔ B2 | 172.16.0.0 /30 |
| B1 ↔ B3 | 172.16.0.4 /30 |
| B1 ↔ B4 | 172.16.0.8 /30 |

### Réseaux logiques (communs à tout le campus)

| Usage       | Réseau        | Passerelle |
| ----------- | ------------- | ---------- |
| Étudiants   | 10.10.0.0 /16 | x.x.x.1    |
| Professeurs | 10.20.0.0 /16 | x.x.x.1    |
| Admins      | 10.30.0.0 /16 | x.x.x.1    |

