<?php
/**
 * Terazi — outlets and labels.
 *
 * label: 'gov' = pro-government, 'ind' = independent, 'opp' = pro-opposition
 * about: why the outlet has its label (owner, editorial line), shown on the
 *        Outlets page. Give 'en' and 'tr' versions, or a single string.
 *
 * These labels are a starting point, not a verdict. Turkish media ownership
 * and editorial lines change often (sales, trustees, editor changes), so
 * review them yourself and edit freely. Labels are read at page-render
 * time, so a change here shows up immediately; no re-fetch needed.
 *
 * Try to keep the pro-government and pro-opposition sides roughly the same
 * size, otherwise the coverage bars lean toward whichever side has more feeds.
 *
 * Feed URLs and notes were checked on 5 Oct 2026. Run `php fetch.php --check`
 * to see which feeds work from your server.
 */
return [
    // ── Pro-government ────────────────────────────────────────────
    'sabah' => [
        'name' => 'Sabah', 'label' => 'gov',
        'site' => 'https://www.sabah.com.tr', 'feed' => 'https://www.sabah.com.tr/rss/gundem.xml',
        'about' => [
            'en' => "Flagship daily of Turkuvaz Media, Turkey's largest media group, owned by the Kalyon Group, a construction company that wins major state contracts. Turkuvaz is run by Serhat Albayrak, brother of Erdoğan's son-in-law Berat Albayrak.",
            'tr' => "Türkiye'nin en büyük medya grubu Turkuvaz Medya'nın amiral gemisi; grup, büyük kamu ihaleleri alan inşaat şirketi Kalyon Grubu'na ait. Turkuvaz'ı, Erdoğan'ın damadı Berat Albayrak'ın kardeşi Serhat Albayrak yönetiyor.",
        ],
    ],
    'yenisafak' => [
        'name' => 'Yeni Şafak', 'label' => 'gov',
        'site' => 'https://www.yenisafak.com', 'feed' => 'https://www.yenisafak.com/rss?xml=gundem',
        'about' => [
            'en' => "Conservative daily owned by the Albayrak Group, a conglomerate in construction and municipal services; one of the most consistently pro-government newspapers.",
            'tr' => "İnşaat ve belediye hizmetleri alanında faaliyet gösteren Albayrak Grubu'na ait muhafazakâr gazete; hükümeti en tutarlı biçimde destekleyen gazetelerden biri.",
        ],
    ],
    'ahaber' => [
        'name' => 'A Haber', 'label' => 'gov',
        'site' => 'https://www.ahaber.com.tr', 'feed' => 'https://www.ahaber.com.tr/rss/gundem.xml',
        'about' => [
            'en' => "News channel of Turkuvaz Media, the same group as Sabah and Takvim; its bulletins and talk shows consistently defend the government.",
            'tr' => "Sabah ve Takvim ile aynı grup olan Turkuvaz Medya'nın haber kanalı; haber bültenleri ve tartışma programları hükümeti tutarlı biçimde savunur.",
        ],
    ],
    'hurriyet' => [
        'name' => 'Hürriyet', 'label' => 'gov',
        'site' => 'https://www.hurriyet.com.tr', 'feed' => 'https://www.hurriyet.com.tr/rss/gundem',
        'about' => [
            'en' => "For decades the Doğan group's flagship and Turkey's best-known mainstream daily. Since 2018 it has belonged to the Demirören Group, whose late owner Erdoğan Demirören was close to the president and bought it with a loan from state-owned Ziraat Bank; its line has followed the government since.",
            'tr' => "Onlarca yıl Doğan grubunun amiral gemisi ve Türkiye'nin en bilinen ana akım gazetesiydi. 2018'den beri, Cumhurbaşkanı'na yakınlığıyla bilinen merhum Erdoğan Demirören'in kamu bankası Ziraat Bankası'ndan aldığı krediyle satın aldığı Demirören Grubu'na ait; o zamandan beri çizgisi hükümetle uyumlu.",
        ],
    ],
    'cnnturk' => [
        'name' => 'CNN Türk', 'label' => 'gov',
        'site' => 'https://www.cnnturk.com', 'feed' => 'https://www.cnnturk.com/feed/rss/all/news',
        'about' => [
            'en' => "Turkish licence of CNN, owned by the Demirören Group since 2018 together with Hürriyet; its coverage generally follows the government line.",
            'tr' => "CNN'in Türkiye lisansı; 2018'den beri Hürriyet ile birlikte Demirören Grubu'na ait. Haberleri genel olarak hükümet çizgisini izler.",
        ],
    ],
    'trthaber' => [
        'name' => 'TRT Haber', 'label' => 'gov',
        'site' => 'https://www.trthaber.com', 'feed' => 'https://www.trthaber.com/sondakika_articles.rss',
        'about' => [
            'en' => "News channel of the state broadcaster TRT, whose management is appointed by the government. Opposition members of the broadcasting regulator RTÜK regularly publish airtime counts showing it gives the ruling alliance far more coverage than the opposition.",
            'tr' => "Yönetimi hükümet tarafından atanan kamu yayıncısı TRT'nin haber kanalı. RTÜK'ün muhalefet üyeleri, kanalın iktidar ittifakına muhalefetten çok daha fazla yayın süresi ayırdığını gösteren sayımları düzenli olarak yayımlıyor.",
        ],
    ],
    'aa' => [
        'name' => 'Anadolu Ajansı', 'label' => 'gov',
        'site' => 'https://www.aa.com.tr', 'feed' => 'https://www.aa.com.tr/tr/rss/default?cat=guncel',
        'about' => [
            'en' => "State news agency founded in 1920; its general manager is appointed by the government and its reporting reflects official positions.",
            'tr' => "1920'de kurulan devlet haber ajansı; genel müdürü hükümet tarafından atanır ve haberleri resmi görüşleri yansıtır.",
        ],
    ],
    'star' => [
        'name' => 'Star', 'label' => 'gov',
        'site' => 'https://www.star.com.tr', 'feed' => 'https://www.star.com.tr/rss/rss.asp',
        'about' => [
            'en' => "Daily of TürkMedya (also owner of Akşam and 24 TV), owned by the brothers Zeki and Hasan Yeşildağ; Hasan Yeşildağ is a known ally of President Erdoğan. Online only since its print edition closed in 2019.",
            'tr' => "Akşam ve 24 TV'nin de sahibi olan TürkMedya'nın gazetesi; grup Zeki ve Hasan Yeşildağ kardeşlere ait. Hasan Yeşildağ, Cumhurbaşkanı Erdoğan'a yakınlığıyla biliniyor. Basılı baskısı 2019'da kapandı, yalnızca internette yayımlanıyor.",
        ],
    ],
    'takvim' => [
        'name' => 'Takvim', 'label' => 'gov',
        'site' => 'https://www.takvim.com.tr', 'feed' => 'https://www.takvim.com.tr/rss/guncel.xml',
        'about' => [
            'en' => "Tabloid daily of Turkuvaz Media (same owner as Sabah); known for combative front pages aimed at opposition politicians and critical journalists.",
            'tr' => "Turkuvaz Medya'nın (Sabah ile aynı sahip) bulvar gazetesi; muhalefet politikacılarını ve eleştirel gazetecileri hedef alan sert manşetleriyle tanınır.",
        ],
    ],
    'ntv' => [
        'name' => 'NTV', 'label' => 'gov',
        'site' => 'https://www.ntv.com.tr', 'feed' => 'https://www.ntv.com.tr/gundem.rss',
        'about' => [
            'en' => "Mainstream news channel of the Doğuş Group. It was widely criticised for ignoring the 2013 Gezi protests in their first days, and its coverage rarely challenges the government. Some see it as mainstream rather than pro-government.",
            'tr' => "Doğuş Grubu'nun ana akım haber kanalı. 2013'teki Gezi protestolarını ilk günlerde görmezden geldiği için yoğun eleştiri aldı; haberleri hükümeti nadiren sorgular. Bazıları onu hükümet yanlısı değil, ana akım bir kanal olarak görür.",
        ],
    ],
    'haberturk' => [
        'name' => 'Habertürk', 'label' => 'gov',
        'site' => 'https://www.haberturk.com', 'feed' => 'https://www.haberturk.com/rss/kategori/gundem.xml',
        'about' => [
            'en' => "News channel owned for years by the Ciner Group and bought by Can Holding in March 2025. Since September 2025 it has been run by the state fund TMSF, appointed as trustee after an investigation into Can Holding's owners. Its coverage generally follows the government line.",
            'tr' => "Uzun yıllar Ciner Grubu'na ait olan, Mart 2025'te Can Holding'in satın aldığı haber kanalı. Can Holding'in sahiplerine yönelik soruşturmanın ardından Eylül 2025'ten beri kayyum olarak atanan TMSF tarafından yönetiliyor. Haberleri genel olarak hükümet çizgisini izler.",
        ],
    ],
    'yeniakit' => [
        'name' => 'Yeni Akit', 'label' => 'gov',
        'site' => 'https://www.yeniakit.com.tr', 'feed' => 'https://www.yeniakit.com.tr/rss/haber/gundem',
        'about' => [
            'en' => "Hardline Islamist daily and one of Erdoğan's most vocal supporters. The Hrant Dink Foundation's media monitoring has repeatedly found it to be the national paper with the most hate speech.",
            'tr' => "Katı İslamcı çizgide, Erdoğan'ın en yüksek sesli destekçilerinden bir gazete. Hrant Dink Vakfı'nın medya izleme raporları onu defalarca en çok nefret söylemi içeren ulusal gazete olarak saptadı.",
        ],
    ],
    'haberglobal' => [
        'name' => 'Haber Global', 'label' => 'gov',
        'site' => 'https://haberglobal.com', 'feed' => 'https://haberglobal.com/rss/gundem',
        'about' => [
            'en' => "News channel launched in 2018 by Azerbaijan's Global Media Group, which has close ties to Azerbaijan's state oil company SOCAR; its coverage generally supports the Turkish government.",
            'tr' => "Azerbaycan devlet petrol şirketi SOCAR'a yakın Global Media Group'un 2018'de kurduğu haber kanalı; haberleri genel olarak Türkiye hükümetini destekler.",
        ],
    ],
    'tgrthaber' => [
        'name' => 'TGRT Haber', 'label' => 'gov',
        'site' => 'https://www.tgrthaber.com', 'feed' => 'https://www.tgrthaber.com/rss/gundem',
        'about' => [
            'en' => "News channel of İhlas Holding, chaired by Ahmet Mücahid Ören, which also owns the daily Türkiye and the İhlas News Agency (İHA); conservative and consistently pro-government.",
            'tr' => "Yönetim kurulu başkanı Ahmet Mücahid Ören olan, Türkiye gazetesi ve İhlas Haber Ajansı'nın (İHA) da sahibi İhlas Holding'in haber kanalı; muhafazakâr ve tutarlı biçimde hükümet yanlısı.",
        ],
    ],

    // ── Pro-opposition ────────────────────────────────────────────
    'sozcu' => [
        'name' => 'Sözcü', 'label' => 'opp',
        'site' => 'https://www.sozcu.com.tr', 'feed' => 'https://www.sozcu.com.tr/feeds-rss-category-sozcu',
        'about' => [
            'en' => "Secular-nationalist daily founded in 2007 by Burak Akbay; one of the government's loudest critics. In July 2025 the regulator RTÜK took its TV channel, Sözcü TV, off air for 10 days over its coverage of the protests after Istanbul mayor Ekrem İmamoğlu was detained.",
            'tr' => "Burak Akbay'ın 2007'de kurduğu laik-milliyetçi gazete; hükümetin en sert eleştirmenlerinden. Kanalı Sözcü TV'nin yayını, İstanbul Büyükşehir Belediye Başkanı Ekrem İmamoğlu'nun gözaltına alınmasının ardından çıkan protestoları yayınlaması nedeniyle Temmuz 2025'te RTÜK kararıyla 10 gün durduruldu.",
        ],
    ],
    'cumhuriyet' => [
        'name' => 'Cumhuriyet', 'label' => 'opp',
        'site' => 'https://www.cumhuriyet.com.tr', 'feed' => 'https://www.cumhuriyet.com.tr/rss/son_dakika.xml',
        'about' => [
            'en' => "Founded in 1924, one of Turkey's oldest dailies; secular and centre-left. Its executives and journalists were prosecuted and jailed after 2016 in a trial widely condemned by press-freedom groups.",
            'tr' => "1924'te kurulan, Türkiye'nin en köklü gazetelerinden; laik ve merkez sol. Yöneticileri ve gazetecileri 2016'dan sonra basın özgürlüğü örgütlerinin geniş çapta kınadığı bir davada yargılanıp hapsedildi.",
        ],
    ],
    'birgun' => [
        'name' => 'BirGün', 'label' => 'opp',
        'site' => 'https://www.birgun.net', 'feed' => 'https://www.birgun.net/rss/home',
        'about' => [
            'en' => "Left-wing daily founded in 2004; strongly critical of the government and close to the socialist left.",
            'tr' => "2004'te kurulan sol gazete; hükümete karşı çok eleştirel ve sosyalist sola yakın.",
        ],
    ],
    'halktv' => [
        'name' => 'Halk TV', 'label' => 'opp',
        'site' => 'https://halktv.com.tr', 'feed' => 'https://halktv.com.tr/service/rss.php',
        'about' => [
            'en' => "TV news channel built around opposition commentators and politicians, owned by businessman Cafer Mahiroğlu; it has been fined repeatedly by the regulator RTÜK.",
            'tr' => "Muhalefet yorumcuları ve politikacıları etrafında şekillenen haber kanalı; sahibi iş insanı Cafer Mahiroğlu. RTÜK'ten defalarca ceza aldı.",
        ],
    ],
    'diken' => [
        'name' => 'Diken', 'label' => 'opp',
        'site' => 'https://www.diken.com.tr', 'feed' => 'https://www.diken.com.tr/feed/',
        'about' => [
            'en' => "News site launched in 2014 by Harun Simavi, grandson of Hürriyet's founder Sedat Simavi, as a counterweight to pro-government media; defends secularism and is sharply critical of the government.",
            'tr' => "Hürriyet'in kurucusu Sedat Simavi'nin torunu Harun Simavi'nin 2014'te hükümet yanlısı medyaya karşı bir denge olarak kurduğu haber sitesi; laikliği savunur ve hükümete karşı çok eleştireldir.",
        ],
    ],
    'karar' => [
        'name' => 'Karar', 'label' => 'opp',
        'site' => 'https://www.karar.com', 'feed' => 'https://www.karar.com/service/rss.php',
        'about' => [
            'en' => "Conservative daily launched in 2016 by journalists who left pro-government papers. It began close to former prime minister Ahmet Davutoğlu and now criticises the government from a conservative standpoint; it says it has been shut out of official advertising.",
            'tr' => "Hükümet yanlısı gazetelerden ayrılan gazetecilerin 2016'da kurduğu muhafazakâr gazete. Eski başbakan Ahmet Davutoğlu'na yakın bir çizgiyle başladı, bugün hükümeti muhafazakâr bir bakışla eleştiriyor; resmi ilanlardan dışlandığını söylüyor.",
        ],
    ],
    'evrensel' => [
        'name' => 'Evrensel', 'label' => 'opp',
        'site' => 'https://www.evrensel.net', 'feed' => 'https://www.evrensel.net/rss/haber.xml',
        'about' => [
            'en' => "Left-wing daily founded in 1995 that focuses on workers' rights and labour struggles; it comes from the same tradition as the Labour Party (EMEP).",
            'tr' => "1995'te kurulan, emekçi hakları ve işçi mücadelelerine odaklanan sol gazete; Emek Partisi (EMEP) ile aynı gelenekten gelir.",
        ],
    ],
    'yenicag' => [
        'name' => 'Yeniçağ', 'label' => 'opp',
        'site' => 'https://www.yenicaggazetesi.com', 'feed' => 'https://www.yenicaggazetesi.com/rss',
        'about' => [
            'en' => "Nationalist daily founded in 2002, owned by Ahmet Çelik, a founding member and former MP of the İYİ Party; criticises the government and its nationalist ally, the MHP.",
            'tr' => "2002'de kurulan milliyetçi gazete; sahibi İYİ Parti'nin kurucu üyesi ve eski milletvekili Ahmet Çelik. Hükümeti ve milliyetçi müttefiki MHP'yi eleştirir.",
        ],
    ],
    'korkusuz' => [
        'name' => 'Korkusuz', 'label' => 'opp',
        'site' => 'https://www.korkusuz.com.tr', 'feed' => 'https://www.korkusuz.com.tr/rss',
        'about' => [
            'en' => "Daily launched in 2014 by Sözcü's owner Burak Akbay as part of the Sözcü group; shares its secular-nationalist, anti-government line.",
            'tr' => "Sözcü'nün sahibi Burak Akbay'ın 2014'te Sözcü grubu bünyesinde çıkardığı gazete; Sözcü'nün laik-milliyetçi, hükümet karşıtı çizgisini paylaşır.",
        ],
    ],
    'artigercek' => [
        'name' => 'Artı Gerçek', 'label' => 'opp',
        'site' => 'https://artigercek.com', 'feed' => 'https://artigercek.com/service/rss.php',
        'about' => [
            'en' => "News site founded in Germany in 2017, alongside Artı TV, by veteran journalist Celal Başlangıç (d. 2024); left-wing, gives wide coverage to the Kurdish political movement, and is critical of the government.",
            'tr' => "Usta gazeteci Celal Başlangıç'ın (ö. 2024) 2017'de Almanya'da Artı TV ile birlikte kurduğu haber sitesi; sol, Kürt siyasi hareketine geniş yer verir ve hükümete eleştireldir.",
        ],
    ],
    'kisadalga' => [
        'name' => 'Kısa Dalga', 'label' => 'opp',
        'site' => 'https://kisadalga.net', 'feed' => 'https://kisadalga.net/service/rss.php',
        'about' => [
            'en' => "Online news site edited by journalist Kemal Göktaş; it focuses on investigations, corruption and rights cases, and is critical of the government.",
            'tr' => "Gazeteci Kemal Göktaş'ın genel yayın yönetmenliğini yaptığı haber sitesi; araştırma haberleri, yolsuzluk ve hak ihlallerine odaklanır, hükümete eleştireldir.",
        ],
    ],
    'pencere' => [
        'name' => 'Gazete Pencere', 'label' => 'opp',
        'site' => 'https://www.gazetepencere.com', 'feed' => 'https://www.gazetepencere.com/feed/',
        'about' => [
            'en' => "Launched in 2019 as Turkey's first PDF-only daily by journalists Yavuz Oğhan and İzzet Doğan. Critical of the government, though it says it wants to open a dialogue between Turkey's political camps.",
            'tr' => "Gazeteciler Yavuz Oğhan ve İzzet Doğan'ın 2019'da Türkiye'nin yalnızca PDF olarak yayımlanan ilk gazetesi olarak kurduğu yayın. Hükümete eleştirel; farklı siyasi kesimler arasında diyalog kurmayı amaçladığını söylüyor.",
        ],
    ],
    'sol' => [
        'name' => 'soL', 'label' => 'opp',
        'site' => 'https://haber.sol.org.tr', 'feed' => 'https://haber.sol.org.tr/rss.xml',
        'about' => [
            'en' => "News portal of the Communist Party of Turkey (TKP) tradition; opposes the government from the socialist left.",
            'tr' => "Türkiye Komünist Partisi (TKP) geleneğinin haber portalı; hükümete sosyalist soldan muhalefet eder.",
        ],
    ],
    // Kronos changes domain after access bans (kronos34.news now redirects to kronos42.news).
    // If its feed starts failing, look up the current domain and update both addresses.
    'kronos' => [
        'name' => 'Kronos', 'label' => 'opp',
        'site' => 'https://kronos34.news', 'feed' => 'https://kronos34.news/tr/feed/',
        'about' => [
            'en' => "News site edited from exile by Doğan Ertuğrul, who has lived in Austria since 2016; blocked in Turkey by court orders, which is why its address keeps changing. Turkish prosecutors allege it is linked to the Gülen movement; the site calls itself independent.",
            'tr' => "2016'dan beri Avusturya'da yaşayan Doğan Ertuğrul'un yurt dışından yönettiği haber sitesi; Türkiye'de mahkeme kararlarıyla erişime engellendiği için adresi sürekli değişiyor. Savcılık sitenin Gülen hareketiyle bağlantılı olduğunu iddia ediyor; site ise kendini bağımsız olarak tanımlıyor.",
        ],
    ],

    // ── Independent ───────────────────────────────────────────────
    'bianet' => [
        'name' => 'bianet', 'label' => 'ind',
        'site' => 'https://bianet.org', 'feed' => 'https://bianet.org/rss/bianet',
        'about' => [
            'en' => "Rights-focused news network run by the IPS Communication Foundation; funded mainly by grants, including from Sweden, rather than by a business owner.",
            'tr' => "IPS İletişim Vakfı'nın yürüttüğü, hak haberciliğine odaklanan haber ağı; bir patron yerine çoğunlukla hibelerle, İsveç kaynaklı hibeler dahil, finanse ediliyor.",
        ],
    ],
    'medyascope' => [
        'name' => 'Medyascope', 'label' => 'ind',
        'site' => 'https://medyascope.tv', 'feed' => 'https://medyascope.tv/feed/',
        'about' => [
            'en' => "Online news and video platform founded in 2015 by veteran journalist Ruşen Çakır; funded by reader donations and international grants, it hosts commentators from across the spectrum.",
            'tr' => "Usta gazeteci Ruşen Çakır'ın 2015'te kurduğu internet haber ve video platformu; okur bağışları ve uluslararası hibelerle finanse ediliyor, farklı siyasi görüşlerden yorumculara yer veriyor.",
        ],
    ],
    'serbestiyet' => [
        'name' => 'Serbestiyet', 'label' => 'ind',
        'site' => 'https://serbestiyet.com', 'feed' => 'https://serbestiyet.com/feed/',
        'about' => [
            'en' => "Liberal news and opinion site edited by Yıldıray Oğur and run as a non-profit association; it calls itself “Turkey's grey area” between the two camps.",
            'tr' => "Yıldıray Oğur'un genel yayın yönetmenliğini yaptığı, kâr amacı gütmeyen bir dernek olarak yayımlanan liberal haber ve yorum sitesi; kendini iki kamp arasındaki “Türkiye'nin gri alanı” olarak tanımlıyor.",
        ],
    ],
    'euronews' => [
        'name' => 'Euronews Türkçe', 'label' => 'ind',
        'site' => 'https://tr.euronews.com', 'feed' => 'https://tr.euronews.com/rss',
        'about' => [
            'en' => "Turkish service of the pan-European news channel Euronews; covers Turkey from outside its political camps.",
            'tr' => "Avrupa çapında yayın yapan Euronews'un Türkçe servisi; Türkiye'yi siyasi kampların dışından izler.",
        ],
    ],
    'bbcturkce' => [
        'name' => 'BBC Türkçe', 'label' => 'ind',
        'site' => 'https://www.bbc.com/turkce', 'feed' => 'https://feeds.bbci.co.uk/turkce/rss.xml',
        'about' => [
            'en' => "Turkish service of the BBC World Service, publicly funded in the UK and bound by the BBC's impartiality rules.",
            'tr' => "BBC World Service'in Türkçe servisi; Birleşik Krallık'ta kamu kaynaklarıyla finanse edilir ve BBC'nin tarafsızlık kurallarına tabidir.",
        ],
    ],
    'dwturkce' => [
        'name' => 'DW Türkçe', 'label' => 'ind',
        'site' => 'https://www.dw.com/tr', 'feed' => 'https://rss.dw.com/rdf/rss-tur-all',
        'about' => [
            'en' => "Turkish service of Deutsche Welle, Germany's public international broadcaster, funded by the German federal budget.",
            'tr' => "Almanya'nın uluslararası kamu yayıncısı Deutsche Welle'nin Türkçe servisi; Alman federal bütçesinden finanse edilir.",
        ],
    ],
    'indyturk' => [
        'name' => 'Independent Türkçe', 'label' => 'ind',
        'site' => 'https://www.indyturk.com', 'feed' => 'https://www.indyturk.com/rss.xml',
        'about' => [
            'en' => "Turkish edition of the British Independent, launched in 2019 under licence by the Saudi Research and Media Group, a publisher tied to the Saudi royal family; not aligned with either Turkish camp.",
            'tr' => "İngiliz Independent'ın Türkçe baskısı; 2019'da Suudi kraliyet ailesiyle bağlantılı yayıncı Saudi Research and Media Group tarafından lisansla kuruldu. Türkiye'deki iki kamptan hiçbirine yakın değil.",
        ],
    ],
    'dunya' => [
        'name' => 'Dünya', 'label' => 'ind',
        'site' => 'https://www.dunya.com', 'feed' => 'https://www.dunya.com/rss',
        'about' => [
            'en' => "Business daily that Nezih Demirkent turned into an economics paper in 1981 under principles of impartiality; it focuses on markets and economic policy rather than party politics.",
            'tr' => "Nezih Demirkent'in 1981'de tarafsızlık ilkeleriyle bir ekonomi gazetesine dönüştürdüğü yayın; parti siyasetinden çok piyasalara ve ekonomi politikasına odaklanır.",
        ],
    ],

    // ── Tried on 5 Oct 2026 and left out ──────────────────────────
    // Milliyet: its feed no longer includes links to the articles.
    // Akşam: its feed is mostly foreclosure sale notices.
    // Türkiye: same owner as TGRT Haber, so it would count that newsroom twice.
    // T24, Gazete Oksijen, Nefes, ANKA, Tele1: no working public feed found.
    // Politikyol: its feed has been empty since April 2026.
];
