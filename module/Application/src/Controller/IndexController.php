<?php
/**
 * @Synopsis : Ce script est le controlleur IndexController
 * il permet de récuperer une nouvelle liste de cartes et
 * de valider la solution
 * @copyright Copyright (c) 2018-2018 Ositel Groupe
 * @license   
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;
use Zend\Http\Request;
use Zend\Http\Client;
use Zend\Session\Container;

class IndexController extends AbstractActionController
{
    /*
     * Cette méthode est l'action index du controlleur
     */
    public function indexAction()
    {
        
        return new ViewModel();
        
    }
    
    /*
     * Cette méthode est l'action getCardsAction du controlleur
     * elle permet de récuper une nouvelle liste des cartes
     * on appellant le webservice distant
     */
    
    public function getCardsAction()
    {
        
        // on instancie un nouveau objet Session
        $session = new Container('base');
        
        // L'url du webservice
        $webServicUrl = "https://recrutement.local-trust.com/test/cards/57187b7c975adeb8520a283c";

        // On crée un objet http client
        $client = new Client($webServicUrl, array(
                            'sslverifypeer' => null,
                            'sslallowselfsigned' => null,
                        ));
        
        // On envoie la requête
        $response = $client->send();

        // On décode le corp du résultat retournée
        $result = json_decode($response->getBody(), true);
        
        // Ce qui nous interesse en premier lieu est l'objet data
        $data = $result["data"];
        
        // puis, l'id de l'exercice
        $exerciceId = $result["exerciceId"];
        
        // on le stock dans la session 
        $session->offsetSet('exerciceId', $exerciceId);
        
        // puis, on récupère l'objet qui contient la lsite des cartes
        $cards = $data["cards"];
        
        // ce tableau accueillera les cartes après le tri
        $sortedCards = array();
        
        // on récupère l'ordre des catégories
        $categoryOrder = $data["categoryOrder"];

        // on le stock aussi dans la session
        $session->offsetSet('categoryOrder', $categoryOrder);

        // de même pour l'ordre des valeurs
        $valueOrder = $data["valueOrder"];
        
        // et on le met aussi dans la Session
        $session->offsetSet('valueOrder', $valueOrder);
        
        // ensuite, on mouline sur la liste des ordres par catégorie
        foreach ($categoryOrder as $category) {
            
            // puis, par valeur
            foreach ($valueOrder as $order) {
                
                // ensuite sur les cartes retournées
                foreach ($cards as $card) {
                    
                    // la catégorie de la carte en cours
                    $cardCategory = $card["category"];
                    
                    // et la valeur de la carte en cours
                    $cardValue = $card["value"];
                    
                    // si, les deux matchent avec l'ordre de la catégorie
                    // en cours est l'ordre de la valeur en cours
                    if($cardCategory == $category && $cardValue == $order)
                    {
                        
                        // alors, on les stock dans le tableau du résultat final
                        $sortedCards[] = array("category" => "$cardCategory", "value" => "$cardValue");
                        
                    }
                }
                
            }
        
        }
        
        // on pousse les cartes triées dans un tableau de Session
        // pour une utilisation ultérieure
        $session->offsetSet('cards', $sortedCards);

        // on récupère l'objet Response
        $response = $this->getResponse();
        
        // pour y éffectuer quelques modifications
        // avant l'envoie
        $response->getHeaders()->addHeaderLine( 'Content-Type', 'application/json' );
        
        // on y stock le résultat final encodé  en json
        $response->setContent(json_encode($sortedCards));
        
        // et on le retourne
        return $response;
            

        
    }

    
    /*
     * Cette méthode est l'action checkSolutionAction du controlleur
     * elle permet de checker la liste des cartes triées selon l'ordre
     * défini par le webservice on l'appellant avec des paramètres
     * additionnels
     */
    
    public function checkSolutionAction()
    {
        
        // on instancie un nouveau objet Session        
        $session = new Container('base');
        
        // on récupère l'exercieId de la Session
        $exerciceId = $session->offsetGet('exerciceId');

        // même chose pour la liste des cartes triées
        $cards = $session->offsetGet('cards');
        
        // la liste des ordes par catégorie
        $categoryOrder = $session->offsetGet('categoryOrder');
        
        // et les ordres par valeur
        $valueOrder = $session->offsetGet('valueOrder');

        // ceci est le contenu du body qui sera envoyé en POST au webservice
        $content = array("cards" => $cards, "categoryOrder" => $categoryOrder, "valueOrder" => $valueOrder);
        
        // l'url du web sevice check solution
        $webServicUrl = "https://recrutement.local-trust.com/test/$exerciceId";

        // on instancie un objet http client
        $client = new Client($webServicUrl);
        
        // on déclare que le type de contenu est Json
        // la vérification ssl sur false
        $client
            ->setHeaders([
                'Content-Type' => 'application/json',
            ])
            ->setOptions(['sslverifypeer' => false])
            ->setOptions(['sslallowselfsigned' => false])
            ->setMethod('POST')
            ->setRawBody(Json::encode($content));

       
        // et on envoie le tout
        $result = $client->send();
        
        // si la requête a été envoyée avec succès, et que le status code retourné est 200
        if ($result->isSuccess() && $result->getStatusCode() == 200) {
            
            // On dit que la réponse est correcte et en affiche le status code retourné
            $ajaxOutput = 'Réponse correcte, status code : '.$result->getStatusCode();
            
        } else {// Sinon,
            
            // On dit que la réponse est incorrecte et en affiche la raison retournée
            $ajaxOutput = 'Réponse incorrecte, status code : '.$result->getStatusCode();
            
            $ajaxOutput .= '<br />Raison : '.$result->getReasonPhrase();
            
            $ajaxOutput .= "<br />La solution : ".$result->getBody();
            
        }

        // on récupère l'objet Response        
        $response = $this->getResponse();
        
        // pour y éffectuer quelques modifications
        // avant l'envoie
        $response->getHeaders()->addHeaderLine( 'Content-Type', 'text/html' );
        
        // on y stock le résultat final encodé  en JSon        
        $response->setContent(utf8_encode($ajaxOutput));
        
        // et on retourne le tout
        return $response;

    }
    
}
