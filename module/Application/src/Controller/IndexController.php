<?php
/**
 * @Synopsis : Ce script est le controlleur IndexController
 * il permet de r�cuperer une nouvelle liste de cartes et
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
     * Cette m�thode est l'action index du controlleur
     */
    public function indexAction()
    {
        
        return new ViewModel();
        
    }
    
    /*
     * Cette m�thode est l'action getCardsAction du controlleur
     * elle permet de r�cuper une nouvelle liste des cartes
     * on appellant le webservice distant
     */
    
    public function getCardsAction()
    {
        
        // on instancie un nouvelle objet Session
        $session = new Container('base');
        
        // L'url du webservice
        $webServicUrl = "https://recrutement.local-trust.com/test/cards/57187b7c975adeb8520a283c";

        // On cr�e un objet http client
        $client = new Client($webServicUrl, array(
                            'sslverifypeer' => null,
                            'sslallowselfsigned' => null,
                        ));
        
        // On envoie la requ�te
        $response = $client->send();

        // on d�code le corp du r�sultat retourn�e
        $result = json_decode($response->getBody(), true);
        
        // Ce qui nous interesse en premier est l'objet data
        $data = $result["data"];
        
        // puis, l'id de l'exercice
        $exerciceId = $result["exerciceId"];
        
        // on le stock dans la session 
        $session->offsetSet('exerciceId', $exerciceId);
        
        // puis, on r�cup_re l'objet qui contient la lsite des cartes
        $cards = $data["cards"];
        
        // ce tableau acceuillera les cartes apr�s le tri
        $sortedCards = array();
        
        // on r�cup�re l'ordre des cat�gorie
        $categoryOrder = $data["categoryOrder"];

        // on le stock aussi dans la session
        $session->offsetSet('categoryOrder', $categoryOrder);

        // de m�me pour l'ordre des valeurs
        $valueOrder = $data["valueOrder"];
        
        // et on le met aussi dans la session
        $session->offsetSet('valueOrder', $valueOrder);
        
        // ensuite, on boucle sur la liste des ordres par cat�gorie
        foreach ($categoryOrder as $category) {
            
            // puis, par valeur
            foreach ($valueOrder as $order) {
                
                // ensuite sur les cartes retourn�es
                foreach ($cards as $card) {
                    
                    // la cat�gorie de la carte en cours
                    $cardCategory = $card["category"];
                    
                    // et la valeur de la cate en cours
                    $cardValue = $card["value"];
                    
                    // si, les deux matchent avec l'ordre de la c�t�gorie
                    // en cours est l'ordre de la valeur en cours
                    if($cardCategory == $category && $cardValue == $order)
                    {
                        
                        // alors, on les stock dans le tableau du r�sultat final
                        $sortedCards[] = array("category" => "$cardCategory", "value" => "$cardValue");
                        
                    }
                }
                
            }
        
        }
        
        // on pousse les cartes tri�es dans un tableau de session
        // pour une utilisation ult�rieure
        $session->offsetSet('cards', $sortedCards);

        // on r�cup�re l'objet response
        $response = $this->getResponse();
        
        // pour y �ffectuer quelques modifications
        // avant l'envoie
        $response->getHeaders()->addHeaderLine( 'Content-Type', 'application/json' );
        
        // on y stoque le r�sultat final encod�  en json
        $response->setContent(json_encode($sortedCards));
        
        // et on le retourne
        return $response;
            

        
    }

    
    /*
     * Cette m�thode est l'action checkSolutionAction du controlleur
     * elle permet de checker la liste des cartes tri�es selon l'orde
     * d�fini par le webservice on l'appellant avec des param�tres
     * aditionnels
     */
    
    public function checkSolutionAction()
    {
        
        // on instancie un nouvelle objet Session        
        $session = new Container('base');
        
        // on r�cup�re l'exercieId de la session
        $exerciceId = $session->offsetGet('exerciceId');

        // m�me chose pour la liste des cartes tri�es
        $cards = $session->offsetGet('cards');
        
        // la liste des ordes par cat�gorie
        $categoryOrder = $session->offsetGet('categoryOrder');
        
        // et les ordres par valeur
        $valueOrder = $session->offsetGet('valueOrder');

        // ceci est le contenu du body qui sera envoy� en POST au webservice
        $content = array("cards" => $cards, "categoryOrder" => $categoryOrder, "valueOrder" => $valueOrder);
        
        // l'url du web sevice check solution
        $webServicUrl = "https://recrutement.local-trust.com/test/$exerciceId";

        // on instancie un objet http client
        $client = new Client($webServicUrl);
        
        // on d�clar� que le type de contenu est json
        // la v�rification ssl sur false
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
        
        // si la requ�te a �t� envoy�e avec succ�s, et que le code retourn� est 200
        if ($result->isSuccess() && $result->getStatusCode() == 200) {
            
            $ajaxOutput = 'R�ponse correcte, status code : '.$result->getStatusCode();
            
        } else {// Sinon,
            
            $ajaxOutput = 'R�ponse incorrecte, status code : '.$result->getStatusCode();
            
            $ajaxOutput .= '<br />Raison : '.$result->getReasonPhrase();
            
            $ajaxOutput .= "<br />La solution : ".$result->getBody();
            
        }

        // on r�cup�re l'objet response        
        $response = $this->getResponse();
        
        // pour y �ffectuer quelques modifications
        // avant l'envoie
        $response->getHeaders()->addHeaderLine( 'Content-Type', 'text/html' );
        
        // on y stoque le r�sultat final encod�  en json        
        $response->setContent(utf8_encode($ajaxOutput));
        
        // et on retourne le tout
        return $response;

    }
    
}
