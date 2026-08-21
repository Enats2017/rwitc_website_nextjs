<?php 
include_once('../bootstrap.php');
  
  $pageTitle ='SADDLE CLOTH';        
  $design = new Design();
  $design->js =""; 
  $design->jqueryJs = "";
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');  
  $design->writeContentPageStyles();
  ?>
  <style type="text/css">
  #leftArea.col-lg-9 { overflow-x: auto; padding-top: 10px !important; margin: -100px 0px 0 0; }
  #leftArea.col-lg-9 table { width: 100% !important; max-width: 100%; }
  #leftArea.col-lg-9 > *:first-child { margin-top: 0 !important; }
  #leftArea.col-lg-9 br:first-child,
  #leftArea.col-lg-9 p:empty:first-child { display: none !important; }
  </style>
  <?php
  
  include_once('../rwitc_upload/static/SADDLECLOTH.HTM');   

  ?>
  <script>
  document.addEventListener('DOMContentLoaded', function () {
      var perPage = 15;
      var tables = document.querySelectorAll('#leftArea table');
                      
      tables.forEach(function (table) {
          var rows = Array.from(table.querySelectorAll('tr')).filter(function (row) {
              return row.querySelectorAll('td').length > 0;
          });

          if (rows.length <= perPage) return;

          var totalPages = Math.ceil(rows.length / perPage);
          var currentPage = 1;

          var nav = document.createElement('div');
          nav.style.textAlign = 'center';
          nav.style.margin = '15px 0 30px';

          function renderPage(page) {
              rows.forEach(function (row, i) {
                  row.style.display = (i >= (page - 1) * perPage && i < page * perPage) ? '' : 'none';
              });

              nav.innerHTML = '';

              var prevBtn = document.createElement('button');
              prevBtn.textContent = 'Prev';
              prevBtn.disabled = (page === 1);
              prevBtn.style.margin = '0 6px';
              prevBtn.onclick = function () { currentPage--; renderPage(currentPage); };
              nav.appendChild(prevBtn);

              var label = document.createElement('span');
              label.textContent = ' Page ' + page + ' of ' + totalPages + ' ';
              nav.appendChild(label);

              var nextBtn = document.createElement('button');
              nextBtn.textContent = 'Next';
              nextBtn.disabled = (page === totalPages);
              nextBtn.style.margin = '0 6px';
              nextBtn.onclick = function () { currentPage++; renderPage(currentPage); };
              nav.appendChild(nextBtn);
          }

          table.parentNode.insertBefore(nav, table.nextSibling);
          renderPage(currentPage);
      });
  });
  </script>
  <?php
  
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object