<h3>Légende</h3>
<table>
<tr><td colspan="2"><b>Types de surveillance représentés</b></td></tr>
<tr><td><img src="images/fixed.png"></td>
    <td>Caméra de type fixe ou pivotante. Cette icône est également utilisée par défaut lorsque le type de surveillance est inconnu.<br/>
        Tag : <tt>camera:type=fixed</tt> ou <tt>static</tt> ou <tt>panning</tt></td></tr>
<tr><td><img src="images/dome.png"></td>
    <td>Caméra de type dôme. A priori capable de pivoter à 360°.<br/>
        Tag : <tt>camera:type=dome</tt></td></tr>
<tr><td><img src="images/guard.png"></td>
    <td>Gardien humain, ou guérite.<br/>
        Tag : <tt>surveillance:type=guard</tt></td></tr>

<tr><td colspan="2"><b>Couleur des icônes</b></td></tr>
<tr><td><img src="images/fixedRed.png"></td>
    <td>Surveillance du domaine public.<br/>
        Note : à titre personnel, j'utilise ce tag pour les caméras à vocation de surveillance d'un espace privé mais qui "empiètent" un peu trop sur l'espace public).<br/>
        Tag : <tt>surveillance=public</tt></td></tr>
<tr><td><img src="images/fixedBlue.png"></td>
    <td>Surveillance de l'extérieur d'un bâtiment ou d'une cour privée<br/>
        Tag : <tt>surveillance=outdoor</tt></td></tr>
<tr><td><img src="images/fixedGreen.png"></td>
    <td>Surveillance d'un espace intérieur<br/>
        Tag : <tt>surveillance=indoor</tt></td></tr>


<tr><td colspan="2"><b>Dessin des faisceaux</b></td></tr>
<tr><td><img src="images/beam.png"></td>
    <td>Le dessin des faisceaux se base sur plusieurs paramètres : <ul>
        <li>Le type de caméra : le faisceau des dômes est un cercle ; le faisceau des caméras statiques est un angle de 40°.</li>
        <li>La hauteur de caméra : chaque mètre de hauteur augmente la longueur du faisceau de 7m, avec un minimum de 14m et un maximum de 84m.<br/>
            Tag : <tt>height=<i>hauteur en mètres</i></tt></li>
        <li>La direction de la caméra : pour les caméras statiques, la direction observée (par rapport au nord) par la caméra.<br/>
            Tag : <tt>camera:direction=<i>degrés par rapport au nord</i></tt></li>
        <li>L'angle de la caméra : pour les caméras statiques, plus l'angle de la caméra avec l'horizontale est important, plus la longueur du faisceau est réduite.<br/>
            Tag : <tt>camera:angle=<i>degrés par rapport à l'horizontale</i></tt></li>
        </ul></td>
</tr>
<tr><td colspan="2"><b>Note importante : </b> : les informations reproduites ici ne sont qu'une représentation symbolique et ne reflètent peut-être pas la réalité. L'ensemble des informations est issue de la base OpenStreetMap ; ces informations ne sont pas exhaustives (malheureusement, loin de là. La CNIL indique qu'il y a plus de 800 000 caméras sur le territoire français ; au 1er octobre 2012, on ne dénombre qu'un peu moins de 15 000 caméras dans OpenStreetMap pour l'ensemble du globe). </td></tr>
</table>
<h3>Nouvelles</h3>
<h4>2012-10-07</h4>
Des statistiques sur les tags utilisés sur les noeuds <tt>man_made=surveillance</tt> sont maintenant disponibles <a href="tags.php">ici</a>.<br/>
<h4>2012-09-30</h4>
Le code de ce site est maintenant disponible sur <a href="https://github.com/khris78/osmcamera">GitHub</a>.<br/>
