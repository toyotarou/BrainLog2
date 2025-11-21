<!doctype html>
<html lang="ja">
<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">

    <title>shintakudatainput</title>
</head>
<body>
<h1>shintakudatainput</h1>

<div>105	楽天・全米株式インデックス・ファンド(楽天・VTI)</div>
<div>106	eMAXIS Slim 米国株式(S&P500)</div>
<div>118	iFree S&P500インデックス</div>
<div>107	iFreeNEXT インド株インデックス</div>
<div>108	iTrust インド株式</div>
<div>109	たわらノーロード S&P500 - NISAつみたて投資枠</div>
<div>110	eMAXIS Slim 米国株式(S&P500)</div>
<div>111	eMAXIS Slim 全世界株式(オール・カントリー)(オルカン)</div>
<div>112	iFree S&P500インデックス</div>
<div>113	NZAM・ベータ S&P500</div>
<div>119	たわらノーロード 先進国株式</div>
<div>114	eMAXIS Slim 米国株式(S&P500)</div>
<div>115	iFree S&P500インデックス</div>
<div>116	たわらノーロード S&P500</div>
<div>117	iFree S&P500インデックス</div>

<div class="container">
    <form method="POST" action="{{ url('/money/shintakudatainputexecute') }}" id="form_shintakudata_input">
        {{ csrf_field() }}
        <textarea name="shintakudata" style="width: 1000px; height: 600px;"></textarea>
        <button class="btn bg-success text-white">submit</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW"
        crossorigin="anonymous"></script>

</body>
</html>
