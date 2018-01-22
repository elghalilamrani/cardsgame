/* 
 * Synopsis : Ce script js � pour r�le d'invoquer des actions 
 * ddu controlleur IndexController pour r�cuperer les cartes
 * et valider le solution
 * 
 */

$(document).ready(function() {  

   //Lors du click sur le bouton [Jouer]
   $("#playButton").click(function(){
       
       // On vide la div serverResponse
       $("#serverResponse").empty();
       
        // on supprime les class CSS du div
        $("#serverResponse").removeClass();

        // pouis, ajoute ces class
        $("#serverResponse").addClass("row");
                
       //On vide la zone des cartes
       $("#cardsList").empty();
       
       //puis, on affiche le loader Ajax
       $("#ajaxLoader").show();
       
       //on invoque l'action getCardsAction du controlleur indexController
       // pour r�cuper une nouvelle liste de cartes
       $.get('getCards',null, function(data) {

            // on mouline sur le r�sultat
           $.each(data, function(index, value) {
                
               // on cr�e une nouvelle div pour la carte en cours
               var currentCard = '<div class="col-md-4">';
                   currentCard += '<div class="panel panel-default">';
                   currentCard += '<div class="panel-heading">';
                   currentCard += '<h3 class="panel-title">'+value.category+' '+value.value+'</h3>';
                   currentCard += '</div>';
                   currentCard += '<div class="card-container">';
                   currentCard += '<img src="img/'+value.category+'-'+value.value+'.png" width="100%" height="100%">';
                   currentCard += '</div></div></div>';
                   
                 // et on l'ajoute � la zone des cartes
               $("#cardsList").append(currentCard);

               // finalement, on cache le loader Ajax
               $("#ajaxLoader").hide();
       
           });
       });  
   });

    // Lors di clique sur le bouton [checkButton]
   $("#checkButton").click(function(){
       
       // On vide la div serverResponse
       $("#serverResponse").empty();
       
       // on affiche le loader ajax
       $("#ajaxLoader").show();
       
       // on invoque l'action checkSolutionAction du controlleur indexController
       $.get('checkSolution',null, function(data) {
                
                
                // on supprime les class CSS du div
                $("#serverResponse").removeClass();
                
                // pouis, ajoute ces class
                $("#serverResponse").addClass("btn btn-success");
                 
                 // ensuite, on affiche la �rponse du serveur de validation des solutions
                $("#serverResponse").append(data);
                
                // et finalement, on cache l'ajax loader
                $("#ajaxLoader").hide();
                
       });  
   });
   
    // on invoque l'�venement click du bouton [Jouer]
    // apr�s le chargement de la page
    $("#playButton").trigger("click");

});
