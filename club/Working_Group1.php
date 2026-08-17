<?php 
include_once('../bootstrap.php');
$pageTitle ='Vision-Mission';        
$design = new Design();
$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
?>
   <style>
<!--
 /* Font Definitions */
 @font-face
	{font-family:"Cambria Math";
	panose-1:2 4 5 3 5 4 6 3 2 4;
	mso-font-charset:1;
	mso-generic-font-family:roman;
	mso-font-format:other;
	mso-font-pitch:variable;
	mso-font-signature:0 0 0 0 0 0;}
@font-face
	{font-family:Cambria;
	panose-1:2 4 5 3 5 4 6 3 2 4;
	mso-font-charset:0;
	mso-generic-font-family:roman;
	mso-font-pitch:variable;
	mso-font-signature:-1610611985 1073741899 0 0 159 0;}
 /* Style Definitions */
 p.MsoNormal, li.MsoNormal, div.MsoNormal
	{mso-style-unhide:no;
	mso-style-qformat:yes;
	mso-style-parent:"";
	margin-top:0in;
	margin-right:0in;
	margin-bottom:10.0pt;
	margin-left:0in;
	line-height:115%;
	mso-pagination:widow-orphan;
	font-size:11.0pt;
	font-family:"Cambria","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;
	mso-bidi-font-family:"Times New Roman";}
h4
	{mso-style-priority:9;
	mso-style-unhide:no;
	mso-style-qformat:yes;
	mso-style-link:"Heading 4 Char";
	mso-margin-top-alt:auto;
	margin-right:0in;
	mso-margin-bottom-alt:auto;
	margin-left:0in;
	mso-pagination:widow-orphan;
	mso-outline-level:4;
	font-size:12.0pt;
	font-family:"Times New Roman","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;
	font-weight:bold;}
p
	{mso-style-noshow:yes;
	mso-style-priority:99;
	mso-margin-top-alt:auto;
	margin-right:0in;
	mso-margin-bottom-alt:auto;
	margin-left:0in;
	mso-pagination:widow-orphan;
	font-size:12.0pt;
	font-family:"Times New Roman","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;}
p.MsoListParagraph, li.MsoListParagraph, div.MsoListParagraph
	{mso-style-priority:34;
	mso-style-unhide:no;
	mso-style-qformat:yes;
	margin-top:0in;
	margin-right:0in;
	margin-bottom:10.0pt;
	margin-left:.5in;
	line-height:115%;
	mso-pagination:widow-orphan;
	font-size:11.0pt;
	font-family:"Cambria","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;
	mso-bidi-font-family:"Times New Roman";}
span.Heading4Char
	{mso-style-name:"Heading 4 Char";
	mso-style-noshow:yes;
	mso-style-priority:9;
	mso-style-unhide:no;
	mso-style-locked:yes;
	mso-style-link:"Heading 4";
	font-family:"Times New Roman","serif";
	mso-ascii-font-family:"Times New Roman";
	mso-hansi-font-family:"Times New Roman";
	mso-bidi-font-family:"Times New Roman";
	font-weight:bold;}
p.msolistparagraphcxspfirst, li.msolistparagraphcxspfirst, div.msolistparagraphcxspfirst
	{mso-style-name:msolistparagraphcxspfirst;
	mso-style-unhide:no;
	margin-top:0in;
	margin-right:0in;
	margin-bottom:0in;
	margin-left:.5in;
	margin-bottom:.0001pt;
	line-height:115%;
	mso-pagination:widow-orphan;
	font-size:11.0pt;
	font-family:"Cambria","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;
	mso-bidi-font-family:"Times New Roman";}
p.msolistparagraphcxspmiddle, li.msolistparagraphcxspmiddle, div.msolistparagraphcxspmiddle
	{mso-style-name:msolistparagraphcxspmiddle;
	mso-style-unhide:no;
	margin-top:0in;
	margin-right:0in;
	margin-bottom:0in;
	margin-left:.5in;
	margin-bottom:.0001pt;
	line-height:115%;
	mso-pagination:widow-orphan;
	font-size:11.0pt;
	font-family:"Cambria","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;
	mso-bidi-font-family:"Times New Roman";}
p.msolistparagraphcxsplast, li.msolistparagraphcxsplast, div.msolistparagraphcxsplast
	{mso-style-name:msolistparagraphcxsplast;
	mso-style-unhide:no;
	margin-top:0in;
	margin-right:0in;
	margin-bottom:10.0pt;
	margin-left:.5in;
	line-height:115%;
	mso-pagination:widow-orphan;
	font-size:11.0pt;
	font-family:"Cambria","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;
	mso-bidi-font-family:"Times New Roman";}
p.msopapdefault, li.msopapdefault, div.msopapdefault
	{mso-style-name:msopapdefault;
	mso-style-unhide:no;
	mso-margin-top-alt:auto;
	margin-right:0in;
	margin-bottom:10.0pt;
	margin-left:0in;
	line-height:115%;
	mso-pagination:widow-orphan;
	font-size:12.0pt;
	font-family:"Times New Roman","serif";
	mso-fareast-font-family:"Times New Roman";
	mso-fareast-theme-font:minor-fareast;}
span.apple-converted-space
	{mso-style-name:apple-converted-space;
	mso-style-unhide:no;}
span.SpellE
	{mso-style-name:"";
	mso-spl-e:yes;}
span.GramE
	{mso-style-name:"";
	mso-gram-e:yes;}
.MsoChpDefault
	{mso-style-type:export-only;
	mso-default-props:yes;
	font-size:10.0pt;
	mso-ansi-font-size:10.0pt;
	mso-bidi-font-size:10.0pt;}
.MsoPapDefault
	{mso-style-type:export-only;
	margin-bottom:10.0pt;
	line-height:115%;}
@page Section1
	{size:8.5in 14.0in;
	margin:1.0in 1.0in 1.0in 1.0in;
	mso-header-margin:.5in;
	mso-footer-margin:.5in;
	mso-paper-source:0;}
div.Section1
	{page:Section1;}
-->
</style>
<!--[if gte mso 10]>
<style>
 /* Style Definitions */
 table.MsoNormalTable
	{mso-style-name:"Table Normal";
	mso-tstyle-rowband-size:0;
	mso-tstyle-colband-size:0;
	mso-style-noshow:yes;
	mso-style-priority:99;
	mso-style-qformat:yes;
	mso-style-parent:"";
	mso-padding-alt:0in 5.4pt 0in 5.4pt;
	mso-para-margin-top:0in;
	mso-para-margin-right:0in;
	mso-para-margin-bottom:10.0pt;
	mso-para-margin-left:0in;
	line-height:115%;
	mso-pagination:widow-orphan;
	font-size:10.0pt;
	font-family:"Times New Roman","serif";}
</style>
<![endif]--><!--[if gte mso 9]><xml>
 <o:shapedefaults v:ext="edit" spidmax="2050"/>
</xml><![endif]--><!--[if gte mso 9]><xml>
 <o:shapelayout v:ext="edit">
  <o:idmap v:ext="edit" data="1"/>
 </o:shapelayout></xml><![endif]-->
</head>

<body lang=EN-US style='tab-interval:.5in'>

<div class=Section1>

<p class=MsoNormal align=center style='margin-bottom:0in;margin-bottom:.0001pt;
text-align:center;line-height:normal;background:white'><b><u><span
style='font-size:11.5pt;font-family:"Arial","sans-serif";color:#222222'>TRUSTEES
– 2013 / 2014</span></u></b></p>

<p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
justify;line-height:normal;background:white'><span style='font-size:11.5pt;
font-family:"Arial","sans-serif";color:#222222'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></p>

<p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
justify;line-height:normal;background:white'><span style='font-size:11.5pt;
font-family:"Arial","sans-serif";color:#222222'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></p>

<div align=center>

<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width=613
 style='width:459.75pt;background:white;border-collapse:collapse;mso-yfti-tbllook:
 1184;mso-padding-alt:0in 0in 0in 0in'>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
  <td width=319 valign=top style='width:239.35pt;border:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>The Trustees, Gratuity Fund of the RWITC Ltd.</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  </td>
  <td width=294 valign=top style='width:220.55pt;border:solid windowtext 1.0pt;
  border-left:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Champaklal</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> <span class=SpellE>Zaveri</span>,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Gulamhusein</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> A. <span class=SpellE>Vahanvaty</span>,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>The Secretary – Ex-Officio</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Convenor</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> - Mr. B.A. Engineer,
  Secretary.</span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:1'>
  <td width=319 valign=top style='width:239.35pt;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>The Trustees, RWITC Ltd. Provident Fund</span></b></p>
  </td>
  <td width=294 valign=top style='width:220.55pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Champaklal</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> <span class=SpellE>Zaveri</span>,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Gulamhusein</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> A. <span class=SpellE>Vahanvaty</span>,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>The Secretary -&nbsp;&nbsp;Ex-Officio</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Convenor</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> - Mr. B.A. Engineer,
  Secretary.</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:2'>
  <td width=319 valign=top style='width:239.35pt;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>The Trustees, RWITC Ltd., Employees’ Superannuation
  Scheme(Pension Fund)</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  </td>
  <td width=294 valign=top style='width:220.55pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Geoffrey B. <span class=SpellE>Nagpal</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Gulamhusein</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> A. <span class=SpellE>Vahanvaty</span>,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>The Secretary – Ex-Officio</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Convenor</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> - Mr. B.A. Engineer,
  Secretary.</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:3'>
  <td width=319 valign=top style='width:239.35pt;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Club’s Nominee on the Western India Racing Stables Poor
  Employees Benevolent Fund.</span></b></p>
  </td>
  <td width=294 valign=top style='width:220.55pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Vivek</span></span><span style='font-size:11.5pt;font-family:
  "Arial","sans-serif";color:#222222'> Jain, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Champaklal</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> <span class=SpellE>Zaveri</span>,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:4'>
  <td width=319 valign=top style='width:239.35pt;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Club’s representative on the Managing Committee of the Maharashtra
  State National Sports Fund.</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  </td>
  <td width=294 valign=top style='width:220.55pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Champaklal</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> <span class=SpellE>Zaveri</span>,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:5;mso-yfti-lastrow:yes'>
  <td width=319 valign=top style='width:239.35pt;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span lang=EN-GB style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222;mso-ansi-language:EN-GB'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span lang=EN-GB style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222;mso-ansi-language:EN-GB'>Nomination and Appointment of Occupier
  of the Club under the Factories Act 1948.<o:p></o:p></span></b></p>
  </td>
  <td width=294 valign=top style='width:220.55pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Champaklal</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> <span class=SpellE>Zaveri</span>,
  Esquire</span></p>
  </td>
 </tr>
</table>

</div>

<p class=MsoNormal align=center style='margin-bottom:0in;margin-bottom:.0001pt;
text-align:center;line-height:normal;background:white'><span style='font-size:
11.5pt;font-family:"Arial","sans-serif";color:#222222'>&nbsp;</span></p>

<p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
color:#222222;background:white'><br clear=all style='mso-special-character:
line-break'>
</span></p>

<p class=MsoNormal align=center style='margin-bottom:0in;margin-bottom:.0001pt;
text-align:center;line-height:normal;background:white'><span style='font-size:
11.5pt;font-family:"Arial","sans-serif";color:#222222'>&nbsp;</span></p>

<p class=MsoNormal align=center style='margin-bottom:0in;margin-bottom:.0001pt;
text-align:center;line-height:normal;background:white'><b><span
style='font-size:11.5pt;font-family:"Arial","sans-serif";color:#222222'>WORKING
GROUPS – 2013 / 2014</span></b></p>

<p class=MsoNormal align=center style='margin-bottom:0in;margin-bottom:.0001pt;
text-align:center;line-height:normal;background:white'><b><span
style='font-size:11.5pt;font-family:"Arial","sans-serif";color:#222222'>&nbsp;</span></b></p>

<div align=center>

<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width=642
 style='width:481.5pt;margin-left:5.4pt;background:white;border-collapse:collapse;
 mso-yfti-tbllook:1184;mso-padding-alt:0in 0in 0in 0in'>
 <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
  <td width=240 style='width:2.5in;border:solid windowtext 1.0pt;background:
  transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal align=center style='margin-bottom:0in;margin-bottom:.0001pt;
  text-align:center;line-height:normal'><b><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal align=center style='margin-bottom:0in;margin-bottom:.0001pt;
  text-align:center;line-height:normal'><b><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'>Name of the Working Group</span></b></p>
  </td>
  <td width=402 style='width:301.5pt;border:solid windowtext 1.0pt;border-left:
  none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal align=center style='margin-bottom:0in;margin-bottom:.0001pt;
  text-align:center;line-height:normal'><b><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'>Appointed by Committee /
  Invitee</span></b></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:1'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Wagering, Inter-venue Betting &amp; Off-Course Betting <span
  class=SpellE>Centres</span> Working Group.</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Jaydev</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> <span
  class=SpellE>Mody</span>, Esquire&nbsp;&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Dr.
  Ram H. <span class=SpellE>Shroff</span></span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Ibrahim <span class=SpellE>Rahimtoola</span>,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'>- Mr. B.A.
  Engineer, Secretary<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:2'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Annual Sale Working Group.</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Champaklal</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> <span
  class=SpellE>Zaveri</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Geoffrey
  B. <span class=SpellE>Nagpal</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Dr. F.F. <span class=SpellE>Wadia</span></span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:9.0pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. <span
  class=SpellE>Satish</span> <span class=SpellE>Iyer</span>, Registrar, ISB.</span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:3'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Stud Book &amp; DNA Typing Working Groups.</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Stud Book&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>DNA Typing</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Geoffrey
  B. <span class=SpellE>Nagpal</span>, Esquire (Chairman)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Gulamhusein</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> A. <span
  class=SpellE>Vahanvaty</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Z.S. <span class=SpellE>Poonawalla</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Gautam</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Lala</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Dr. F.F. <span class=SpellE>Wadia</span></span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Vispi</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> Patel, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Dr. R.R. <span class=SpellE>Kunchur</span></span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Dilip</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Goculdas</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Padmanabh</span></span><span style='font-size:
  11.5pt;font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Ruia</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Zeyn</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Mizra</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Gurpal</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> Singh, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Ms. <span class=SpellE>Ameeta</span> <span
  class=SpellE>Mehra</span></span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:9.0pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. <span
  class=SpellE>Satish</span> <span class=SpellE>Iyer</span>, Registrar, ISB</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Ms. <span
  class=SpellE>Veena</span> <span class=SpellE>Gupte</span>, Chief Analyst, DNA
  Lab.<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:4'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Veterinary Hospital Working Group.</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Shyam</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> M. <span
  class=SpellE>Ruia</span>, Esquire&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Dr.
  Ram <span class=SpellE>Shroff</span></span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Representative
  of WIRHOA Ltd.</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:9.0pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – In-Charge,
  Equine Hospital<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:5'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Pune</span></b></span><b><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> Race Course &amp; Stands
  Working Group.</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Jaydev</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> <span
  class=SpellE>Mody</span>, Esquire (Chairman)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Khushroo</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> N. <span
  class=SpellE>Dhunjibhoy</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Geoffrey
  B. <span class=SpellE>Nagpal</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenors</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> - Mr. NHS Mani,
  Additional Secretary /</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Mr.
  B.N. <span class=SpellE>Nanjapa</span>, Estate Officer, PRC<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:6'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Turf Club House, <span class=SpellE>Pune</span> Working Group</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Gulamhusein</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> A. <span
  class=SpellE>Vahanvaty</span>, Esquire (Chairman)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Geoffrey
  B. <span class=SpellE>Nagpal</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenors</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> -&nbsp;&nbsp;Mr.
  B.A. Engineer, Secretary</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Mr.
  NHS Mani, Additional Secretary&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Mr.
  John <span class=SpellE>Fernandes</span>, GM, TCH<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:7'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Pune</span></b></span><b><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";color:#222222'> Racing Infrastructure
  Working Group (including Stables &amp; Tracks)</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Khushroo</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> N. <span
  class=SpellE>Dhunjibhoy</span>, Esquire (Chairman)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Geoffrey
  B. <span class=SpellE>Nagpal</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Inderraj</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Anand</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Jiyaji</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Bhosale</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Begum
  <span class=SpellE>Sheherbanoo</span> <span class=SpellE>Lagad</span> (Rep.
  of WIRHOA Ltd.)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-indent:
  6.0pt;line-height:normal'><span style='font-size:10.0pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenors</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> - Mr. NHS Mani,
  Additional Secretary /</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-indent:
  6.0pt;line-height:normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Mr.
  B.N. <span class=SpellE>Nanjapa</span>, Estate Officer, PRC</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-indent:
  6.0pt;line-height:normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:8'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Cups &amp; Trophies Working Group.</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Champaklal</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> <span
  class=SpellE>Zaveri</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Shiven</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> <span
  class=SpellE>Surendranath</span>, Esquire (Rep. of WIRHOA Ltd)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Hoshang</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> <span
  class=SpellE>Nazir</span>, Esquire (Rep. of WIRHOA Ltd.)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Dilip</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Goculdas</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Munis</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> H. <span
  class=SpellE>Varawala</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Tinder <span class=SpellE>Ahluwalia</span>,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-indent:
  6.0pt;line-height:normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'>- Mr. N H S Mani,
  Additional Secretary<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:9'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Mumbai Race Course &amp; Grand Stand&nbsp;Working Group.</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Jaydev</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> M. <span
  class=SpellE>Mody</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Dr.
  Ram H. <span class=SpellE>Shroff</span></span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. N.H.S. Mani,
  Additional Secretary/</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Mr.
  <span class=SpellE>Suhail</span> Mohammed, Estate Officer, MRC.<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:10'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Mumbai Racing Infrastructure Working Group (including Stables and
  Tracks)</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Geoffrey
  B. <span class=SpellE>Nagpal</span>, Esquire (Chairman)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Khushroo</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> N. <span
  class=SpellE>Dhunjibhoy</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Rustom</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Vakil</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Dilip</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Goculdas</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Adil</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Gandhy</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Hemendra</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> M. Shah, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Mr. <span
  class=SpellE>Dara</span> Mehta (Rep. of WIRHOA Ltd.)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Mr. <span class=SpellE>Rehanullah</span> Khan
  (Rep. of WITA Ltd.)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. N.H.S. Mani,
  Additional Secretary /</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Mr. <span
  class=SpellE>Suhail</span> Mohammed, Estate Officer, MRC.<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:11'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Marketing, Media, Public Relations, Live Telecast &amp;
  Website Working Group.</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Vivek</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> Jain, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Avinash</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> Shankar, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Sanjay D. Shah, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Shiven</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Surendranath</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Asif</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Lampwala</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Tariq <span class=SpellE>Vaidya</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Atul</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> H. <span
  class=SpellE>Maru</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Ms. <span class=SpellE>Zinia</span> Lawyer</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Ms. <span class=SpellE>Gitanjali</span> <span
  class=SpellE>Gurbaxani</span></span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Ms. <span class=SpellE>Udita</span> M. <span
  class=SpellE>Nabha</span></span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:8.0pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. N.H.S. Mani,
  Additional Secretary<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:12'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:12.0pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Allocation of Boxes Working Group.</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Vivek</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> Jain, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:8.0pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> - Mr. B.A.
  Engineer, Secretary.<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:13'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Internal Audit Working Group.&nbsp;</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Vivek</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> Jain, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Gulamhusein</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> A. <span
  class=SpellE>Vahanvaty</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. B.A.
  Engineer, Secretary.<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:14'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>RWITC Club House, Mumbai Working Group.</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Champaklal</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> <span
  class=SpellE>Zaveri</span>, Esquire (Chairman)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Khushroo</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> N. <span
  class=SpellE>Dhunjibhoy</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Gulamhusein</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> A. <span
  class=SpellE>Vahanvaty</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Dr. <span class=SpellE>Kamal</span> R. Gupta</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Ramesh</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Seksaria</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Padmakar</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> V. Desai,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Chandraprakash</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif";mso-bidi-font-weight:
  bold'> J. <span class=SpellE>Halwasia</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Bal <span class=SpellE>Govind</span> <span
  class=SpellE>Chokani</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Pradeep</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> G. <span
  class=SpellE>Vora</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Asif</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Lampwala</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. NHS Mani,
  Additional Secretary<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:15'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>RWITC Gymnasium Working</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Group</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Vivek</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> Jain, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Zameer</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Vahanvaty</span>, <span class=SpellE>Esquie</span></span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Rikeen</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Dalal</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Asif</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Lampwala</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Veer <span class=SpellE>Uday</span> Shah, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. N.H.S. Mani,
  Additional Secretary<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:16'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Legal Working Group</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Vivek</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> Jain, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Khushroo</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> N. <span
  class=SpellE>Dhunjibhoy</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Jaydev</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> <span
  class=SpellE>Mody</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. B.A.
  Engineer, Secretary<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:17'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Working Group to Liaise with Govt.</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Vivek</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> Jain, Esquire&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. B.A.
  Engineer, Secretary<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:18'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Bookmakers’ Working Group</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Champaklal</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> <span
  class=SpellE>Zaveri</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Hasmukh</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Chawda</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Hemendra</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> M. Shah, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. B.A.
  Engineer, Secretary<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:19'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Security &amp; Vigilance Working Group (Mumbai &amp; <span
  class=SpellE>Pune</span>)</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Khushroo</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> N. <span
  class=SpellE>Dhunjibhoy</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Gulamhusein</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> A. <span
  class=SpellE>Vahanvaty</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Padmakar</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> V. Desai,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:9.0pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. NHS Mani,
  Additional Secretary<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:20'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Stabling Norms Working Group</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Geoffrey
  B. <span class=SpellE>Nagpal</span>, Esquire (Chairman)</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Shiven</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Surendranath</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Asif</span></span><span style='font-size:11.5pt;
  font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> <span
  class=SpellE>Lampwala</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>S.K. <span class=SpellE>Sunderji</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  mso-bidi-font-weight:bold'>Sangramsingh</span></span><span style='font-size:
  11.5pt;font-family:"Arial","sans-serif";mso-bidi-font-weight:bold'> Joshi,
  Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. NHS Mani,
  Additional Secretary<o:p></o:p></span></p>
  </td>
 </tr>
 <tr style='mso-yfti-irow:21;mso-yfti-lastrow:yes'>
  <td width=240 valign=top style='width:2.5in;border:solid windowtext 1.0pt;
  border-top:none;background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>&nbsp;</span></b></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
  color:#222222'>Officials’ Appraisal Working Group</span></b></p>
  </td>
  <td width=402 valign=top style='width:301.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  background:transparent;padding:0in 5.4pt 0in 5.4pt'>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Geoffrey
  B. <span class=SpellE>Nagpal</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Gulamhusein</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> A. <span
  class=SpellE>Vahanvaty</span>, Esquire</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>&nbsp;</span></p>
  <p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
  normal'><span class=SpellE><span style='font-size:11.5pt;font-family:"Arial","sans-serif"'>Convenor</span></span><span
  style='font-size:11.5pt;font-family:"Arial","sans-serif"'> – Mr. B.A.
  Engineer, Secretary<o:p></o:p></span></p>
  </td>
 </tr>
</table>

</div>

<p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
normal;background:white'><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
color:#222222'>&nbsp;</span></p>

<p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
normal;background:white'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
color:#222222'>&nbsp;</span></b></p>

<p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
normal;background:white'><span class=GramE><b><u><span style='font-size:11.5pt;
font-family:"Arial","sans-serif";color:#222222'>Note:</span></u></b><b><span
style='font-size:11.5pt;font-family:"Arial","sans-serif";color:#222222'>&nbsp;&nbsp;WIRHOA
LTD. nominated M/s <span class=SpellE>Pervez</span> <span class=SpellE>Andhyarujina</span>
&amp; <span class=SpellE>Keky</span> <span class=SpellE>Patell</span> for Oats
Working Group, if any.</span></b></span></p>

<p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
normal;background:white'><b><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
color:#222222'>&nbsp;</span></b></p>

<p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;line-height:
normal;background:white'><b><u><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
color:#222222'>Note:</span></u></b><span style='font-size:11.5pt;font-family:
"Arial","sans-serif";color:#222222'>&nbsp;&nbsp;</span></p>

<p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
justify;line-height:normal;background:white'><span style='font-size:11.5pt;
font-family:"Arial","sans-serif";color:#222222'>&nbsp;</span></p>

<p class=MsoListParagraph style='margin-bottom:0in;margin-bottom:.0001pt;
text-align:justify;text-indent:-.25in;line-height:normal;background:white'><span
style='font-size:12.0pt;font-family:"Arial","sans-serif";color:#222222'>1.</span><span
style='font-size:7.0pt;font-family:"Times New Roman","serif";color:#222222'>&nbsp;&nbsp;&nbsp;
</span><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
color:#222222'>It was decided that the Chairman and Secretary will be
ex-officio in all Working Groups.</span></p>

<p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
justify;text-indent:3.0pt;line-height:normal;background:white'><span
style='font-size:12.0pt;font-family:"Arial","sans-serif";color:#222222'>&nbsp;</span></p>

<p class=MsoListParagraph style='margin-bottom:0in;margin-bottom:.0001pt;
text-align:justify;text-indent:-.25in;line-height:normal;background:white'><span
style='font-size:12.0pt;font-family:"Arial","sans-serif";color:#222222'>2.</span><span
style='font-size:7.0pt;font-family:"Times New Roman","serif";color:#222222'>&nbsp;&nbsp;&nbsp;
</span><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
color:#222222'>It was decided that problems faced by Race-goers regarding <span
class=SpellE>totalisators</span> etc. will be dealt with by the Mumbai Race
Course, Grand Stand Working Group.</span></p>

<p class=MsoNormal style='margin-bottom:0in;margin-bottom:.0001pt;text-align:
justify;text-indent:3.0pt;line-height:normal;background:white'><span
style='font-size:12.0pt;font-family:"Arial","sans-serif";color:#222222'>&nbsp;</span></p>

<p class=MsoListParagraph style='margin-bottom:0in;margin-bottom:.0001pt;
text-align:justify;text-indent:-.25in;line-height:normal;background:white'><span
style='font-size:12.0pt;font-family:"Arial","sans-serif";color:#222222'>3.</span><span
style='font-size:7.0pt;font-family:"Times New Roman","serif";color:#222222'>&nbsp;&nbsp;&nbsp;
</span><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
color:#222222'>Matters concerning the Club’s dispensary will be dealt with by
the Mumbai Race Course &amp; Grand Stand Working Group.</span></p>

<p class=MsoNormal style='margin-top:0in;margin-right:0in;margin-bottom:0in;
margin-left:.5in;margin-bottom:.0001pt;text-align:justify;text-indent:3.0pt;
line-height:normal;background:white'><span style='font-size:12.0pt;font-family:
"Arial","sans-serif";color:#222222'>&nbsp;</span></p>

<p class=MsoListParagraph style='margin-bottom:0in;margin-bottom:.0001pt;
text-align:justify;text-indent:-.25in;line-height:normal;background:white'><span
style='font-size:12.0pt;font-family:"Arial","sans-serif";color:#222222'>4.</span><span
style='font-size:7.0pt;font-family:"Times New Roman","serif";color:#222222'>&nbsp;&nbsp;&nbsp;
</span><span style='font-size:11.5pt;font-family:"Arial","sans-serif";
color:#222222'>Whilst matters concerning Vigilance will be dealt with by the
Stewards, the appeals from persons for return of mobile phones confiscated will
continue to be dealt with by the Secretary.</span></p>

<p class=MsoNormal align=center style='margin-bottom:0in;margin-bottom:.0001pt;
text-align:center;line-height:normal;background:white'><span style='font-size:
11.5pt;font-family:"Arial","sans-serif";color:#222222'>-----------------------</span></p>

<p class=MsoNormal>&nbsp;</p>

</div>

<?php                   
  $design->closeDiv();
  $design->rightArea();  
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
$design = NULL; // release object