<?php

include_once('bootstrap.php');

include_once('lib/sweepstake.class.php');



$sweepstakeObj = new Sweepstake($db);



$pageTitle = 'Sweepstakes';

$design = new Design();



$design->startPage("$pageTitle");

$design->writeLogoTickerMenu();

$design->openDiv("contentWrapper");

$design->openDiv("infoWrapper", "col-lg-12");

$design->openDiv("leftArea", 'col-lg-9');
$design->writeContentPageStyles();
?>

<style type="text/css">
    #leftArea.col-lg-9 .contentTable {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border: 1px solid #e2e6e4;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(11, 61, 36, 0.06);
        margin-top: 0 !important;
        font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
    }

    #leftArea.col-lg-9 .contentTable th {
        background: #04160c;
        color: #fff;
        padding: 14px 16px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.2px;
        text-align: left;
        border: none;
    }

    #leftArea.col-lg-9 .contentTable th:first-child {
        text-align: center;
    }

    #leftArea.col-lg-9 .contentTable td {
        padding: 16px;
        font-size: 14px;
        line-height: 1.55;
        color: #2b332f;
        border-top: 1px solid #e2e1d8;
        background: #fff;
        vertical-align: top;
    }

    #leftArea.col-lg-9 .contentTable tr:hover td {
        background: #f8faf5;
    }

    #leftArea.col-lg-9 .contentTable td:first-child {
        text-align: center;
        white-space: nowrap;
        font-weight: 600;
        color: #0f5c33;
    }

    #leftArea.col-lg-9 .contentTable td.alignLeft {
        text-align: left;
    }

    #leftArea.col-lg-9 .contentTable a {
        color: #0b6d2a;
        font-weight: 700;
        text-decoration: none;
    }

    #leftArea.col-lg-9 .contentTable a:hover {
        color: #15923c;
        text-decoration: underline;
    }

    .sweepBack {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 22px;
        padding: 9px 14px;
        border: 1px solid #e2e6e4;
        border-radius: 10px;
        background: #fff;
        color: #0f5c33;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 3px 10px rgba(11, 61, 36, 0.05);
    }

    @media (max-width: 600px) {

        #leftArea.col-lg-9 .contentTable th,
        #leftArea.col-lg-9 .contentTable td {
            padding: 12px 10px;
            font-size: 13px;
        }
    }
</style>

<?php

$sweeptstakeID = getParameterNumber('id', 0);

// fetch all articles

if ($sweeptstakeID == 0) {

    $allSweepstake = $sweepstakeObj->getAllSweepstakes();



?>

    <!-- <span class="about-eyebrow">Horse Racing</span>
    <h2>Sweepstakes</h2>
    <p class="sweepSubtitle">View current and past sweepstakes and race details</p> -->
    <hr class="sweepDivider" />

    <table class="contentTable" style="margin-top:20px;">

        <col width="15%" />

        <tr>

            <th>DATE</th>

            <th>RACE</th>

        </tr>

        <?php foreach ($allSweepstake as $sweepstakeInfo) { ?>

            <tr>

                <td><?php echo date("d M Y", strtotime($sweepstakeInfo['sweepstake_date'])); ?></td>

                <td class="alignLeft" style="padding-left: 5px;">

                    <a href='/sweepstakes.php?id=<?php echo $sweepstakeInfo['id']; ?>'><?php echo $sweepstakeInfo['title']; ?></a><br />

                    (<?php echo $sweepstakeInfo['comments']; ?>)

                </td>

            </tr>

        <?php } ?>

    </table>

<?php } else if ($sweeptstakeID > 0) {

    $sweepstakeDetails = $sweepstakeObj->getSweepstakeById($sweeptstakeID);

    echo "<a class='sweepBack' href='/sweepstakes.php'>Back</a>";

    include_once(SWEEPSTAKES_BASE . '/' . $sweepstakeDetails['filename']);
} ?>

<?php

$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();

$design = NULL; // release object

?>

<div id="links">

    <a href="http://www.kouiki-kansai.jp/img/">Discount Valentino Wedges Store</a>

    <a href="http://www.arlestourisme.com/img/">Discount Valentino Pumps Store</a>

    <a href="http://www.wvbop.com/img/">Sale Christian Louboutin Sandals Online</a>

    <a href="http://www.eco-tour.jp/image/">Sale Christian Louboutin Boots Online</a>

    <a href="http://www.benzonfund.dk/img/">Valentino Booties Price</a>

    <a href="http://www.rwitc.com/tai/">2015 Ray Ban Aviator Folding Brown Gradient</a>

    <a href="http://www.kuwazawa.co.jp/images/">Cheap Christian Louboutin Boots Online Sale</a>

    <a href="http://www.arvtsc.org/img/">Valentino Pumps Price</a>

    <a href="http://www.nepfoiskola.hu./img/files/">Where to buy Cheap Valentino Booties</a>

    <a href="http://www.visitmeadecounty.org/wp-content/uploads/2010/">Buy Cheap Christian Louboutin Seakers Store</a>

    <a href="http://www.wbpdcl.co.in/img/">Cheap Valentino Boots Online Sale</a>

    <a href="http://www.addpac.com/news/index.php">Tom Ford Round Acetate Sunglasses Black</a>

    <a href="http://www.angloamericano.edu.br/wp-content/uploads/2014/">50 Off Tom Ford Fany Dual Rim Sunglasses</a>

    <a href="http://www.etbrick.com/img/">Prada Curved Temple Sunglasses For Sale</a>

    <a href="http://www.artforce.hu/pictures/img/">Buy Tom Ford Anoushka Butterfly Sunglasses</a>

    <a href="http://www.hjedwards.co.uk/img/">Prada Heritage Hexagonal Sunglasses Outlet</a>

    <a href="http://www.addpac.com/pdf/pdf/index.php">Discount Gucci Diamantissima Butterfly Sunglasses</a>

    <a href="http://www.theelectricalwarehouse.com/img/">Cheap Ray Ban Aviator RB9037 2015 Free Delivery</a>

    <a href="http://www.theelectricalwarehouse.com/product/">Where to buy Cheap Valentino Pumps</a>

    <a href="http://www.dogworld.co.uk/products/">Fendi Limited Edition Colorblock Sunglasses For Sale</a>

    <a href="http://www.dogworld.co.uk/img/">Discount Ray Ban Cats 5000 Classic Sunglasses</a>

    <a href="http://www.rwitc.com/tai/new/">Cheap Christian Louboutin Sandals Online Sale</a>

    <a href="http://www.xterraplanet.com/trailmix/img/index.php">Moncler Coat Women's Spring Autumn Hooded cappu</a>

    <a href="http://www.xterraplanet.com/img/index.php">Moncler Coat Mens Emeric</a>

    <a href="http://www.tourismhrc.com/onlinetraining/img/index.php">Moncler Coat Aliso with Belt</a>

    <a href="http://www.wildwonders.org/wp-content/uploads/2008/index.php">Moncler Coat Miscae</a>

    <a href="http://www.openfind.com/solutionday/uploads/index.php">Moncler Coat Anthime Grey</a>

    <a href="http://www.martinjurisch.com/images/uploads/index.php">Cheap Moncler Jackets For Men</a>

    <a href="http://www.tam-sang.com/images/tam/index.php">Discount Moncler Jackets Moncler Coats On Sale</a>

    <a href="http://www.africa.com/html/index.php">Price Of Moncler Jackets Where To Buy Cheap Moncler</a>

    <a href="http://www.africa.com/images/public/index.php">Real Moncler Jackets Athentic Moncler Coats</a>

    <a href="http://www.draugyste.lt/wp-content/public/index.html">Moncler Gerbois Puffer jackets Black Women Outlet</a>

    <a href="http://www.draugyste.lt/wp-content/uploads/2010/index.html">Moncler Oversize Collar Hooded Puffer jackets Plum Women Outlet</a>

    <a href="http://www.ksrcas.edu/images/uploads/index.php">Cheap Moncler Jackets For Women</a>

    <a href="http://www.bervina.com/wp-content/public/index.html">Moncler Amey Asymmetric Zip Puffer Jacket Black Women Outlet</a>

    <a href="http://www.crawfordguesthouse.com/mail4/index.html">Moncler Nylon and Jersey Zip Hoodie Black Outlet</a>

    <a href="http://turisms.jaunjelgava.lv/img/index.html">Moncler Peplum Puffer Jacket Fuchsia Women Outlet</a>

    <a href="http://www.megalegend.com/public/20150129/index.php">Men Barbour Waxed Jackets Outlet 2015 Sale Online</a>

</div>
<script>
    document.getElementById("links").style.display = "none"
</script>