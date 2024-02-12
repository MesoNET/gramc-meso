$( document ).ready(function() {

    // Définition du bouton retour vers le haut
    let scrollTop = document.querySelector('#scrollTop')
    
    //Ecouteur du scroll de l'utilisateur
    window.addEventListener('scroll', ()=>{
        if(scrollY > 400){
            scrollTop.style.opacity = '1';
        }
        else{
            scrollTop.style.opacity = '0';
        }
    })

    scrollTop.addEventListener('click', function(){
        document.documentElement.scrollTop = 0;
    })
})

$(document).ready(function(){
    $('*[data-href]').click(function(){
        window.location = $(this).data('href');
    });
});


$(document).ready(function(){
    let pourcentage = $('*[pourcentage]')
    var options = {
        series: [pourcentage.attr('pourcentage')],
        chart: {
            type: 'radialBar',
            height: 125,
            width: 125,
            offsetY: -20,
            sparkline: {
                enabled: true
            }
        },
        plotOptions: {
            radialBar: {
                startAngle: -90,
                endAngle: 90,
                track: {
                    background: "#e7e7e7",
                    strokeWidth: '97%',
                    margin: 5, // margin is in pixels
                    dropShadow: {
                        enabled: true,
                        top: 2,
                        left: 0,
                        color: '#999',
                        opacity: 1,
                        blur: 2
                    }
                },
                dataLabels: {
                    name: {
                        show: false
                    },
                    value: {
                        offsetY: -2,
                        fontSize: '15px'
                    }
                }
            }
        },
        grid: {
            padding: {
                top: -10
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                shadeIntensity: 0.4,
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 50, 53, 91]
            },
        },
        labels: ['Progression de'+[pourcentage.attr('pourcentage')]],
    };
        console.log('options', options)
        var chart = new ApexCharts(document.querySelector("#progress-bar"), options);
        chart.render();
});


$( document ).ready(function() {
    //Récupérer "En voir plus..." 
    let more_menu = $('.more')

    // Y a-til un ou plusieurs "more menu" ?
    if (more_menu.length != 0)
    {
        let container = more_menu[0].parentNode.parentNode.parentNode.parentNode
            
        if(container.className != "section_administrateur" && container.className != "section_president")
        {
    
            // Dès qu'on a un li.priorite2, on ne l'affiche pas
            // 
            $('li.priorite2').each(function(){
                this.style.display = 'none'
                let more_locals = $(this).siblings('.more');
                if (more_locals.length > 0)
                {
                    more_local = more_locals[0];
                    more_local.style.display = 'block';
                }
            })
    
            // Binder les li "En voir plus..."
            let envoirplus = more_menu.parent().children('.more')
            envoirplus.on('click', function(){
                
                // Bouton En voir plus: on affiche les li de priorité 2 et on le transforme en "en voir moins"
                if(this.classList.contains('more')){
                    // On travaille sur les éléments de priorité 2 frères de l'élément cliqué
                    $(this).siblings('li.priorite2').each(function(){
                        this.style.display = 'initial'
                    })
                    this.innerHTML = 'En voir moins...'
                    this.classList.add('less')
                    this.classList.remove('more')
                }
                
                // Bouton en voir moins: on cache les li de priorité 2 et on le transforme en "en voir plus"
                else if(this.classList.contains('less')){
                    $(this).siblings('li.priorite2').each(function(){
                        this.style.display = 'none'
                    })
                    this.innerHTML = 'En voir plus...'
                    this.classList.add('more')
                    this.classList.remove('less')
                }
            })
        }
    }
})

// Changer la classe du panneau "Administrateur" pour le réduire ou le développer
// On fait une requête ajax afin que cela soit pérenne durant la session
function admin_redexp() {    
    if(this.className == 'role_admin_reduit'){
        this.className = 'role_admin'
    }
    else{
        this.className = 'role_admin_reduit'
    }
    let h = $(this).data('href');
    $.ajax({
        type: 'GET',
        url: h, 
        processData: false,
        contentType: false,
    });
}
$(document).ready(function() {
    $('.role_admin').on('click', admin_redexp);
    $('.role_admin_reduit').on('click', admin_redexp);
})

// Réduire et développer le menu pour enregistrer / annuler / fermer
$(document).ready(function() {

    $('#panneau_enregistrer .menu').on('click', function(){
        if(this.className == 'menu'){
            this.className = 'menu_ferme'
            this.parentNode.className = 'panneau_ferme'
            console.log(this.parentNode)
        }
        else{
            this.className = 'menu'
            this.parentNode.className = ''
        }
        console.log(this.parentNode)
    })
})
