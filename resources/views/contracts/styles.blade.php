<style>
@page {
  size: A4;
  margin: 30mm 25mm;
}

body {
  font-family: "Times New Roman", serif;
  font-size: 10pt;
  line-height: 1.8;
  text-align: justify;
}

.title {
  text-align: center;
  font-weight: bold;
  text-transform: uppercase;
  font-size: 1.7rem;
  margin-bottom: 25px;
}

.article-title {
  font-weight: bold;
  margin-top: 20px;
}

.line {
  display: inline-block;
  min-width: 200px;
  border-bottom: 1px solid #000;
  text-align: center;
}

.page-break {
  page-break-after: always;
}

.no-break {
  page-break-inside: avoid;
}

table {
  width: 100%;
  border-collapse: collapse;
  page-break-inside: avoid;
}

.clause {
  margin-left: 30px;
  {{-- text-indent: -28px; --}}
  margin-bottom: 10px;
}

.clause-letter {
  margin-left: 50px;
  {{-- line-height: 1.4; --}}
}
</style>
