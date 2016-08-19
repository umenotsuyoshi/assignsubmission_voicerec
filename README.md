# voicerec
本モジュールは、Moodle上で録音問題を作成できるMoodleの「課題プラグイン」です。
語学用のコースで使っていただくことを想定しています。

Moodle用の録音用ツールとしては、「Online audio recording」（https://moodle.org/plugins/assignsubmission_onlineaudio）というプラグインがあります。
しかし、録音部分のプログラムにAdobe Flashを使用しています。
Adobe Flashはスマホやタブレットでは使用できません。PCでも徐々にデフォルトではブロックされるようになってきました。
Adobe Flash、Java アプレットなどのブラウザプラグインを使用したプログラムが動作する環境はここ数年のうちになくなるものと考えられます。  
そのため、「Online audio recording」の置き換えとして、録音部分だけをJavaScriptで書き換えました。
ほとんどの部分はそのまま「Online audio recording」のソースを流用させていただきました。  
今後は、Web上での録音はこのような形になっていくはずなのですが、まだ全てのブラウザでその手段が実装されているわけではありません。  
現状では、動作する環境を非常に選ぶモジュールとなっていることをご了承ください。  

##動作条件

Moodle2.7以降で動作します。  
PCではChrome、Firefox、Operaで確認しています。Edge、IE、Safariでは動作しません。  
スマホ、タブレットでは、 Android上のChromeなどでも動作しますが、現在のところiOS上のブラウザは全て動作しません。  
録音出来る、出来ないはブラウザ側のAPIの実装によって状況がかわりますので、最新の状況は、ブラウザの実装状況サイトでMediaRecorderをご覧ください。  http://caniuse.com/
また、ChromeやOperaはサーバがHTTPSでないと動作しなくなりました。サーバがHTTPの場合は、Firefoxでお試しください。  


##インストール方法
Moodleの/mod/assign/submission/下にvoicerecの名前で配置してください。
  
##既知の問題
他の課題の提出のプラグインと同時に使用すると、「このページから移動しますか？ 入力したデータは保存されません。」のエラーが表示されることがあります。

##注意事項 Warning
本ソフトウェアに起因するいかなる問題についても私は一切の責任を負いません。予めご了承ください。 本ソフトウェアのライセンスはMoodle上のライセンスに従います。  
Moodle is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by　the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Moodle is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details. 
You should have received a copy of the GNU General Public License along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


##Eラーニングに基づく英語とフランス語の学習行動の可視化の試み
科学研究費助成事業(基盤研究B)26284076『Eラーニングに基づく英語とフランス語の学習行動の可視化の試み』 （研究代表者 吉冨 朝子（東京外国語大学）， 研究分担者 井之川 睦美（東京外国語大学），鈴木 陽子（東京外国語大学），斎藤 弘子（東京外国語大学）， 浦田 和幸（東京外国語大学），川口 裕司（東京外国語大学），梅野 毅（東京外国語大学））による研究の一環として公開しています。


