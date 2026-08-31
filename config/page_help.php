<?php

return [
    'default_module' => [
        'title' => 'সিস্টেম পেজ',
        'purpose' => 'এই পেজটি সিস্টেমের নির্দিষ্ট তথ্য দেখা বা ব্যবস্থাপনার জন্য ব্যবহার হয়।',
        'features' => [
            'পেজের শিরোনাম, সারসংক্ষেপ, ফিল্টার ও অ্যাকশন দেখে প্রয়োজনীয় কাজ নির্বাচন করুন।',
            'তালিকার কোনো রেকর্ডে ক্লিক করলে সাধারণত তার বিস্তারিত তথ্য বা সম্পাদনার পেজ খোলে।',
            'সফল, সতর্কতা বা ত্রুটির বার্তা থেকে কাজটির ফলাফল নিশ্চিত করুন।',
        ],
        'notes' => [
            'আপনার অনুমতি অনুযায়ী কিছু বাটন বা তথ্য লুকানো থাকতে পারে।',
            'সংরক্ষণ বা চূড়ান্ত করার আগে গুরুত্বপূর্ণ তথ্য আরেকবার মিলিয়ে নিন।',
        ],
    ],

    'default_mode' => [
        'title' => 'ব্যবহার নির্দেশিকা',
        'description' => 'পেজে থাকা নির্দেশনা ও অ্যাকশন অনুসরণ করে কাজটি সম্পন্ন করুন।',
        'steps' => [
            'প্রথমে পেজের বর্তমান তথ্য ও নির্বাচিত ফিল্টার যাচাই করুন।',
            'প্রয়োজনীয় তথ্য দিন বা উপযুক্ত অ্যাকশন নির্বাচন করুন।',
            'কাজ শেষে ফলাফলের বার্তা এবং পরিবর্তিত তথ্য যাচাই করুন।',
        ],
    ],

    // Order matters: the first matching route pattern is used.
    'modules' => [
        'login' => [
            'title' => 'লগইন',
            'purpose' => 'অনুমোদিত ব্যবহারকারী হিসেবে ISP ব্যবস্থাপনা সিস্টেমে প্রবেশ করার পেজ।',
            'features' => [
                'আপনার নিবন্ধিত ইমেইল ও পাসওয়ার্ড দিয়ে নিরাপদে সাইন ইন করুন।',
                'নিজস্ব ও নিরাপদ ডিভাইস হলে “Remember me” ব্যবহার করে লগইন ধরে রাখতে পারেন।',
                'লগইনের পর আপনার ভূমিকা ও অনুমতি অনুযায়ী মেনু এবং পেজ দেখা যাবে।',
            ],
            'notes' => [
                'শেয়ার করা বা পাবলিক ডিভাইসে “Remember me” নির্বাচন করবেন না।',
                'বারবার লগইন ব্যর্থ হলে ইমেইল যাচাই করে প্রশাসকের সঙ্গে যোগাযোগ করুন।',
            ],
        ],
        'dashboard' => [
            'title' => 'ড্যাশবোর্ড',
            'purpose' => 'ব্যবসার গুরুত্বপূর্ণ বিলিং, বকেয়া, গ্রাহক ও সাপোর্ট তথ্য এক নজরে দেখার সারসংক্ষেপ পেজ।',
            'features' => [
                'মোট পার্টি, সক্রিয় সংযোগ, চলতি মাসের বিল ও বকেয়ার দ্রুত চিত্র দেখুন।',
                'সাম্প্রতিক ইনভয়েস থেকে বিলের অবস্থা ও বকেয়া পর্যবেক্ষণ করুন।',
                'সাম্প্রতিক টিকিট দেখে জরুরি গ্রাহক সমস্যায় দ্রুত প্রবেশ করুন।',
            ],
            'notes' => [
                'সংখ্যাগুলো বর্তমান ডাটাবেজের সারসংক্ষেপ; বিস্তারিত যাচাই করতে সংশ্লিষ্ট তালিকা খুলুন।',
                'আপনার অনুমতি না থাকলে কিছু কার্ড বা অ্যাকশন দেখা নাও যেতে পারে।',
            ],
        ],
        'reseller.*' => [
            'title' => 'রিসেলার পোর্টাল',
            'purpose' => 'রিসেলারের ওয়ালেট, নির্ধারিত গ্রাহক, বকেয়া ইনভয়েস ও কমিশন কার্যক্রম পরিচালনার জায়গা।',
            'features' => [
                'বর্তমান অগ্রিম ব্যালেন্স, দৈনিক ব্যবহারসীমা ও সাম্প্রতিক লেনদেন দেখুন।',
                'নির্ধারিত পার্টির বিল তৈরি বা রিসেলার অগ্রিম থেকে ইনভয়েস পরিশোধ করুন।',
                'কমিশন পরিবর্তন এবং প্রতিটি ওয়ালেট লেনদেনের অডিট ইতিহাস যাচাই করুন।',
            ],
            'notes' => [
                'ওয়ালেট থেকে পেমেন্ট করলে একই টাকা আবার নগদ/ব্যাংক হিসাবে পোস্ট করবেন না।',
                'দৈনিক সীমা ও পর্যাপ্ত ব্যালেন্স না থাকলে পেমেন্ট সম্পন্ন হবে না।',
            ],
        ],
        'customers.*' => [
            'title' => 'পার্টি ব্যবস্থাপনা',
            'purpose' => 'গ্রাহক, বিক্রেতা, রিসেলার ও পণ্য-ক্রেতার পরিচয়, সংযোগ, বিলিং এবং অগ্রিম ব্যালেন্স পরিচালনার মডিউল।',
            'features' => [
                'যোগাযোগ, ঠিকানা, পার্টির ধরন, প্যাকেজ এবং নেটওয়ার্ক সংযোগের তথ্য রাখুন।',
                'ইনভয়েস, পেমেন্ট, সম্পদ, ওয়ারেন্টি ও অগ্রিম ব্যালেন্সের পূর্ণ ইতিহাস দেখুন।',
                'রিসেলার অ্যাসাইনমেন্ট, গ্রেস পিরিয়ড, সার্ভিস ভ্যালিডিটি ও বিশেষ ISP অবস্থা নিয়ন্ত্রণ করুন।',
            ],
            'notes' => [
                'Connection ID ও MikroTik লক্ষ্য পরিবর্তন করলে নেটওয়ার্ক সিঙ্কে প্রভাব পড়তে পারে।',
                'অগ্রিম ব্যালেন্স বা বিলিং তথ্য পরিবর্তনের আগে পার্টি ও টাকার পরিমাণ নিশ্চিত করুন।',
            ],
        ],
        'packages.*' => [
            'title' => 'ইন্টারনেট প্যাকেজ',
            'purpose' => 'মাসিক ইন্টারনেট প্ল্যান, মূল্য, গতি/প্রোফাইল এবং সংশ্লিষ্ট গ্রাহক ব্যবহারের তথ্য পরিচালনা করুন।',
            'features' => [
                'প্যাকেজের নাম, মাসিক মূল্য, MikroTik প্রোফাইল ও সক্রিয় অবস্থা নির্ধারণ করুন।',
                'কোন প্যাকেজ কতজন পার্টি ব্যবহার করছে তা বিস্তারিত পেজে দেখুন।',
                'তালিকা থেকে প্রয়োজনীয় প্যাকেজ দ্রুত সম্পাদনা বা অনুমতি থাকলে মুছুন।',
            ],
            'notes' => [
                'ব্যবহৃত প্যাকেজের মূল্য বা প্রোফাইল বদলালে ভবিষ্যৎ বিল ও সিঙ্কে প্রভাব পড়তে পারে।',
                'MikroTik-এ এক্সপোর্টের আগে প্রোফাইলের নাম ও রেট-লিমিট যাচাই করুন।',
            ],
        ],
        'organizations.*' => [
            'title' => 'প্রিন্ট প্রতিষ্ঠান',
            'purpose' => 'ইনভয়েস, চালান, কোটেশন, ভাউচার ও রিপোর্টে ব্যবহৃত প্রতিষ্ঠানের পরিচয় এবং প্রিন্ট পছন্দ পরিচালনা করুন।',
            'features' => [
                'প্রতিষ্ঠানের নাম, ঠিকানা, যোগাযোগ, লোগো ও Tax/BIN তথ্য সংরক্ষণ করুন।',
                'ডিফল্ট প্রতিষ্ঠান, স্বাক্ষর, ব্যাংক তথ্য ও ফুটার দেখানোর নিয়ম নির্ধারণ করুন।',
                'একাধিক প্রতিষ্ঠানের মধ্যে প্রিন্টের সময় প্রয়োজনীয় প্রতিষ্ঠান নির্বাচন করুন।',
            ],
            'notes' => [
                'ডিফল্ট প্রতিষ্ঠান বদলালে নতুন প্রিন্ট প্রিভিউতে সেটিই আগে নির্বাচিত হবে।',
                'ব্যাংক ও ট্যাক্স তথ্য সংরক্ষণের আগে বানান ও নম্বর ভালোভাবে যাচাই করুন।',
            ],
        ],
        'quotations.*' => [
            'title' => 'কোটেশন',
            'purpose' => 'সম্ভাব্য বিক্রয় বা সেবার মূল্যপ্রস্তাব তৈরি, সংশোধন, প্রিন্ট এবং ইনভয়েসে রূপান্তরের মডিউল।',
            'features' => [
                'পার্টি, পণ্য/সেবা, পরিমাণ, মূল্য, ছাড়, VAT ও বৈধতার সময় লিখুন।',
                'কোটেশনের মোট মূল্য, শর্ত এবং বর্তমান অবস্থা বিস্তারিতভাবে দেখুন।',
                'অনুমোদিত কোটেশন থেকে একই তথ্য ব্যবহার করে ইনভয়েস তৈরি করুন।',
            ],
            'notes' => [
                'কোটেশন আর্থিক লেজারে পেমেন্ট তৈরি করে না; ইনভয়েসে রূপান্তরের পর বিলিং শুরু হয়।',
                'প্রিন্টের আগে প্রতিষ্ঠানের পরিচয়, বৈধতার তারিখ ও শর্ত যাচাই করুন।',
            ],
        ],
        'invoices.*' => [
            'title' => 'ইনভয়েস ও বিক্রয়',
            'purpose' => 'মাসিক ইন্টারনেট বিল ও পণ্য বিক্রয়ের ইনভয়েস তৈরি, চূড়ান্তকরণ, পেমেন্ট এবং প্রিন্ট নথি পরিচালনা করুন।',
            'features' => [
                'পার্টি, বিলিং মাস, লাইন আইটেম, সিরিয়াল, ছাড়, VAT ও নোটসহ ইনভয়েস তৈরি করুন।',
                'Draft, unpaid, partial, paid ও overdue অবস্থা এবং বরাদ্দ করা পেমেন্ট দেখুন।',
                'বিল, ডেলিভারি চালান ও কোটেশন প্রিন্ট করুন অথবা পরবর্তী মাসে কপি করুন।',
            ],
            'notes' => [
                'Finalized ইনভয়েসের আর্থিক ও স্টক প্রভাব থাকতে পারে; চূড়ান্ত করার আগে সব লাইন যাচাই করুন।',
                'পণ্য বিক্রয়ের সিরিয়াল ও গুদাম নির্বাচন ভুল হলে স্টক ট্রেসিং অসঙ্গত হতে পারে।',
            ],
        ],
        'sale-returns.*' => [
            'title' => 'বিক্রয় ফেরত',
            'purpose' => 'বিক্রি হওয়া পণ্য ফেরত নেওয়া, স্টকে ফিরিয়ে আনা এবং গ্রাহকের আর্থিক সমন্বয় নথিভুক্ত করুন।',
            'features' => [
                'মূল ইনভয়েস নির্বাচন করে ফেরতযোগ্য পণ্য ও পরিমাণ নির্ধারণ করুন।',
                'সিরিয়াল-ট্র্যাকড পণ্যের সঠিক সিরিয়াল ফেরত হিসেবে গ্রহণ করুন।',
                'ফেরত নথির আইটেম, মোট মূল্য, স্টক প্রভাব ও পার্টির ইতিহাস দেখুন।',
            ],
            'notes' => [
                'শুধু বাস্তবে ফেরত পাওয়া পণ্য ও সিরিয়াল নির্বাচন করুন।',
                'সংরক্ষণের পর স্টক ও পার্টির ক্রেডিট বদলাতে পারে—মূল ইনভয়েস মিলিয়ে নিন।',
            ],
        ],
        'print-logs.*' => [
            'title' => 'প্রিন্ট ইতিহাস',
            'purpose' => 'কে, কখন, কোন প্রতিষ্ঠান ব্যবহার করে কোন নথি প্রিন্ট করেছেন তার অডিট তালিকা।',
            'features' => [
                'নথির ধরন, নম্বর, প্রতিষ্ঠান, ব্যবহারকারী ও প্রিন্টের সময় দেখুন।',
                'ফিল্টার ব্যবহার করে নির্দিষ্ট নথি বা সময়ের প্রিন্ট কার্যক্রম খুঁজুন।',
                'পুনরায় প্রিন্ট বা বিরোধ যাচাইয়ের সময় আগের রেকর্ডের সঙ্গে মিল করুন।',
            ],
            'notes' => [
                'শুধু Print বাটন চাপলে ইতিহাস তৈরি হয়; শুধু প্রিভিউ খুললে রেকর্ড হয় না।',
                'এই তালিকা অডিটের জন্য—এখান থেকে মূল আর্থিক নথি পরিবর্তন হয় না।',
            ],
        ],
        'payments.*' => [
            'title' => 'পেমেন্ট',
            'purpose' => 'পার্টির কাছ থেকে টাকা গ্রহণ, ইনভয়েসে বরাদ্দ, পেমেন্ট হিস্ট্রি ও মানি রিসিট পরিচালনার মডিউল।',
            'features' => [
                'পার্টি, ইনভয়েস, পেমেন্ট অ্যাকাউন্ট, পদ্ধতি, তারিখ ও পরিমাণ লিখুন।',
                'একটি পেমেন্ট কোন কোন ইনভয়েসে কত টাকা বরাদ্দ হয়েছে তা দেখুন।',
                'স্ট্যান্ডার্ড বা থার্মাল ভাউচার প্রিন্ট করে সংগ্রহের প্রমাণ দিন।',
            ],
            'notes' => [
                'পরিমাণ, পার্টি ও পেমেন্ট অ্যাকাউন্ট ভুল হলে লেজার ও বকেয়া দুটিই প্রভাবিত হবে।',
                'একই লেনদেন দ্বিতীয়বার এন্ট্রি হয়েছে কি না রেফারেন্স দিয়ে যাচাই করুন।',
            ],
        ],
        'bkash-sms-payments.*' => [
            'title' => 'bKash SMS পেমেন্ট',
            'purpose' => 'SMS Forwarder থেকে আসা বা হাতে দেওয়া bKash বার্তা পার্স, যাচাই এবং অনুমোদন করে পেমেন্টে রূপান্তর করুন।',
            'features' => [
                'Transaction ID, প্রেরকের নম্বর, পরিমাণ, সময় ও পার্সিং অবস্থা দেখুন।',
                'অমিল তথ্য ঠিক করে উপযুক্ত পার্টি ও ইনভয়েসের সঙ্গে লেনদেন মিলান।',
                'যাচাই শেষে অনুমোদন করে অ্যাকাউন্ট ও পার্টির লেজারে পেমেন্ট পোস্ট করুন।',
            ],
            'notes' => [
                'Transaction ID ডুপ্লিকেট বা ভুল হলে অনুমোদনের আগে অবশ্যই মূল bKash তথ্য যাচাই করুন।',
                'Raw SMS পরিবর্তন বা ম্যানুয়াল এন্ট্রিতে পরিমাণ ও সময় বিশেষভাবে মিলিয়ে নিন।',
            ],
        ],
        'accounting.*' => [
            'title' => 'অ্যাকাউন্টিং লেজার',
            'purpose' => 'পেমেন্ট, বিক্রয়, খরচ ও পার্টি-ভিত্তিক ডেবিট/ক্রেডিট প্রবাহ একত্রে দেখা এবং রিপোর্ট প্রিন্ট করার জায়গা।',
            'features' => [
                'তারিখ, পার্টি ও লেনদেনের ধরন দিয়ে হিসাবের এন্ট্রি ফিল্টার করুন।',
                'মোট Debit, Credit এবং চলতি Balance দেখে হিসাব মিলান।',
                'একই ফিল্টার ও নির্বাচিত প্রতিষ্ঠানসহ পরিচ্ছন্ন লেজার রিপোর্ট প্রিন্ট করুন।',
            ],
            'notes' => [
                'লেজার সমন্বিত রিপোর্ট; মূল ভুল সংশোধন করতে সংশ্লিষ্ট পেমেন্ট, ইনভয়েস বা খরচ রেকর্ডে যান।',
                'Party Ledger ও সামগ্রিক Accounting Ledger-এর ব্যালেন্সের অর্থ আলাদা হতে পারে।',
            ],
        ],
        'payment-accounts.*' => [
            'title' => 'পেমেন্ট অ্যাকাউন্ট',
            'purpose' => 'Cash, Bank, Mobile Banking বা অন্যান্য হিসাবের টাকা আসা-যাওয়া এবং বর্তমান ব্যালেন্স পর্যবেক্ষণ করুন।',
            'features' => [
                'অ্যাকাউন্টের নাম, ধরন, উদ্বোধনী ব্যালেন্স ও সক্রিয় অবস্থা সংরক্ষণ করুন।',
                'নির্দিষ্ট অ্যাকাউন্ট বা সম্মিলিত Cash Ledger-এ সংগ্রহ ও খরচ দেখুন।',
                'তারিখ ও লেনদেনের ধরন দিয়ে ফিল্টার করে চলতি ব্যালেন্স মিলান।',
            ],
            'notes' => [
                'উদ্বোধনী ব্যালেন্স শুধু শুরু অবস্থার জন্য; দৈনন্দিন সমন্বয় মূল লেনদেন দিয়ে করুন।',
                'পেমেন্ট অ্যাকাউন্ট নির্বাচন ভুল হলে ক্যাশ/ব্যাংক ব্যালেন্স ভুল দেখাবে।',
            ],
        ],
        'employees.*' => [
            'title' => 'কর্মচারী',
            'purpose' => 'কর্মচারীর পরিচয়, পদ, বেতন, বোনাস, সম্পদ, বেতন বৃদ্ধি ও ব্যালেন্সের পূর্ণ রেকর্ড রাখুন।',
            'features' => [
                'যোগাযোগ, যোগদানের তারিখ, পদ, মূল বেতন ও সক্রিয় অবস্থা পরিচালনা করুন।',
                'বেতন বৃদ্ধি, মাসভিত্তিক বেতন/বোনাস এবং কর্মচারীর কাছে থাকা সম্পদ দেখুন।',
                'Balance Sheet থেকে বকেয়া বেতন, পরিশোধ ও নেট অবস্থান যাচাই করুন।',
            ],
            'notes' => [
                'বেতন সংশোধনের কার্যকর তারিখ ভবিষ্যৎ বেতন গণনায় প্রভাব ফেলে।',
                'কর্মচারী নিষ্ক্রিয় করার আগে চলমান দায়িত্ব ও ফেরত না-দেওয়া সম্পদ যাচাই করুন।',
            ],
        ],
        'expenses.*' => [
            'title' => 'বেতন ও খরচ',
            'purpose' => 'অফিস খরচ, কর্মচারীর বেতন/বোনাস এবং সংশ্লিষ্ট পেমেন্ট অ্যাকাউন্ট লেনদেন নথিভুক্ত করুন।',
            'features' => [
                'খরচের ধরন, তারিখ, পরিমাণ, কর্মচারী, অ্যাকাউন্ট ও নোট লিখুন।',
                'ফিল্টার ও সারসংক্ষেপ থেকে নির্দিষ্ট সময়ের মোট খরচ বিশ্লেষণ করুন।',
                'বিস্তারিত পেজে পেমেন্ট তথ্য, এন্ট্রিকারী ও সম্পাদনা ইতিহাস দেখুন।',
            ],
            'notes' => [
                'Salary, Bonus ও সাধারণ Expense-এর হিসাব আলাদা; সঠিক ধরন নির্বাচন করুন।',
                'ভুল অ্যাকাউন্ট বা তারিখ বাছলে লেজার এবং কর্মচারীর Balance Sheet প্রভাবিত হবে।',
            ],
        ],
        'fleet.*' => [
            'title' => 'ফ্লিট ব্যবস্থাপনা',
            'purpose' => 'যানবাহন, দায়িত্বপ্রাপ্ত কর্মী, মাইলেজ, রক্ষণাবেক্ষণ, ছবি/ভিডিও ও চলাচল খরচ পরিচালনার মডিউল।',
            'features' => [
                'যানবাহনের পরিচয়, বর্তমান মাইলেজ, অবস্থা এবং Driver/Helper/Supervisor অ্যাসাইন করুন।',
                'সময় বা মাইলেজভিত্তিক Maintenance Schedule এবং সম্পন্ন কাজ নথিভুক্ত করুন।',
                'যানভিত্তিক খরচ, মেরামত, Due/Overdue ও Duty History রিপোর্ট দেখুন।',
            ],
            'notes' => [
                'সঠিক মাইলেজ না দিলে পরবর্তী রক্ষণাবেক্ষণের Due গণনা ভুল হবে।',
                'Draft রেকর্ড যাচাই করে Finalize করুন; Finalized রেকর্ড সাধারণত আর সম্পাদনাযোগ্য নয়।',
            ],
        ],
        'ip-pools.*' => [
            'title' => 'গ্লোবাল IP Pool',
            'purpose' => 'অ্যাপে সংরক্ষিত Master IP Pool এবং বিভিন্ন MikroTik রাউটারের pool ব্যবহারের কেন্দ্রীয় তালিকা।',
            'features' => [
                'Pool-এর নাম, IP range, next-pool, সংশ্লিষ্ট রাউটার ও নোট দেখুন।',
                'কেন্দ্রীয় তালিকা থেকে pool তথ্য সম্পাদনা বা প্রয়োজনমতো অপসারণ করুন।',
                'রাউটারভিত্তিক pool পেজে গিয়ে live snapshot-এর সঙ্গে Master তালিকা মিলান।',
            ],
            'notes' => [
                'IP range পরিবর্তনের আগে overlap, gateway ও চালু PPP session-এর প্রভাব যাচাই করুন।',
                'অ্যাপের Master তথ্য বদলালেই live MikroTik সবসময় স্বয়ংক্রিয়ভাবে বদলায় না।',
            ],
        ],
        'network-map.*' => [
            'title' => 'FTTX নেটওয়ার্ক ম্যাপ',
            'purpose' => 'OLT, splitter, closure, pole, cabinet, customer endpoint ও fiber link মানচিত্রে আঁকা এবং নথিভুক্ত করুন।',
            'features' => [
                'লোকেশন খুঁজে map style, visibility ও প্রয়োজনীয় node/link tool নির্বাচন করুন।',
                'ম্যাপে ক্লিক করে অবকাঠামোর ধরন, নাম, capacity, status, note ও ছবি সংরক্ষণ করুন।',
                'বিদ্যমান node বা link নির্বাচন করে তথ্য সম্পাদনা এবং network path পর্যবেক্ষণ করুন।',
            ],
            'notes' => [
                'সঠিক স্থানাঙ্ক ও সংযোগ নিশ্চিত করে Save করুন; ভুল link নেটওয়ার্ক topology বিভ্রান্ত করবে।',
                'মাঠের সংবেদনশীল ছবি বা তথ্য আপলোডের আগে প্রতিষ্ঠানের নীতি অনুসরণ করুন।',
            ],
        ],
        'olt-onus.*' => [
            'title' => 'OLT ও ONU ব্যবস্থাপনা',
            'purpose' => 'OLT সংযোগ, ONU live অবস্থা, optical signal, VLAN, discovery, deny list ও protocol command পরিচালনার মডিউল।',
            'features' => [
                'OLT নির্বাচন করে ONU তালিকা, PON port, online/offline অবস্থা ও laser level দেখুন।',
                'Live refresh চালিয়ে cached তথ্য হালনাগাদ এবং নির্দিষ্ট ONU-এর raw output পর্যালোচনা করুন।',
                'Auto Discovery থেকে ONU যোগ, Deny List নিয়ন্ত্রণ এবং vendor-specific protocol/profile সংরক্ষণ করুন।',
            ],
            'notes' => [
                'Refresh, VLAN, port-state বা config command সরাসরি নেটওয়ার্ক ডিভাইসে প্রভাব ফেলতে পারে।',
                'OLT credential ও command template শুধু পরীক্ষিত তথ্য দিয়ে পরিবর্তন করুন।',
            ],
        ],
        'mikrotik-routers.*' => [
            'title' => 'MikroTik ব্যবস্থাপনা',
            'purpose' => 'MikroTik রাউটারের সংযোগ, PPP Profile, IP Pool, PPPoE Secret এবং অ্যাপের তথ্যের সিঙ্ক পরিচালনা করুন।',
            'features' => [
                'রাউটারের IP, API port, credential, সংযোগ অবস্থা ও sync settings রাখুন।',
                'Live MikroTik থেকে profile, pool ও secret snapshot import করে অ্যাপের তথ্যের সঙ্গে তুলনা করুন।',
                'নির্বাচিত App তথ্য রাউটারে export অথবা imported secret থেকে Party তৈরি করুন।',
            ],
            'notes' => [
                'Import, Export বা Delete করার আগে কোন দিকের তথ্য authoritative তা নিশ্চিত করুন।',
                'রাউটারের username/password/port বদলালে connection test করে তারপর সিঙ্ক চালান।',
            ],
        ],
        'tickets.*' => [
            'title' => 'সাপোর্ট টিকিট',
            'purpose' => 'পার্টির অভিযোগ, সাপোর্ট অনুরোধ, অগ্রাধিকার ও টেকনিশিয়ানের কাজের অবস্থা ট্র্যাক করুন।',
            'features' => [
                'পার্টি, বিষয়, বিস্তারিত সমস্যা, priority ও বর্তমান status নথিভুক্ত করুন।',
                'ফিল্টার করে Open, Processing বা সমাপ্ত টিকিট দ্রুত খুঁজুন।',
                'বিস্তারিত পেজে যোগাযোগ ও সমস্যার তথ্য দেখে পরবর্তী পদক্ষেপ নিন।',
            ],
            'notes' => [
                'বিবরণে সমস্যার সময়, এলাকা, ডিভাইস ও ইতিমধ্যে করা পরীক্ষা লিখলে সমাধান দ্রুত হয়।',
                'কাজ শেষ হলে status আপডেট করুন, যাতে খোলা টিকিটের তালিকা নির্ভুল থাকে।',
            ],
        ],
        'in-house-use.*' => [
            'title' => 'In-house সম্পদ ব্যবহার',
            'purpose' => 'কর্মচারীকে অফিসের পণ্য/যন্ত্রাংশ ইস্যু, ফেরত, পুনঃইস্যু এবং অবশিষ্ট দায় ট্র্যাক করুন।',
            'features' => [
                'কর্মচারী, গুদাম, পণ্য, serial/quantity, হস্তান্তরের কারণ ও প্রমাণপত্রসহ Issue তৈরি করুন।',
                'ফেরত নেওয়ার সময় নতুন saleable stock থেকে used returned stock আলাদা রাখুন।',
                'Employee, returned used stock ও পূর্ণ issue/return history রিপোর্ট দেখুন।',
            ],
            'notes' => [
                'Serial-tracked পণ্যে ইস্যু ও ফেরতের সময় প্রকৃত serial অবশ্যই মিলিয়ে নিন।',
                'Used stock নতুন বিক্রয়যোগ্য stock-এর সঙ্গে মেশে না; পুনঃইস্যুতে সঠিক উৎস নির্বাচন করুন।',
            ],
        ],
        'warehouse-movements.*' => [
            'title' => 'স্টক মুভমেন্ট ইতিহাস',
            'purpose' => 'গুদামভিত্তিক Stock In, Stock Out, Transfer, Sale, Return ও Own Use-এর পূর্ণ লেজার।',
            'features' => [
                'গুদাম, পণ্য, মুভমেন্ট ধরন, তারিখ বা রেফারেন্স দিয়ে ইতিহাস খুঁজুন।',
                'প্রতিটি entry-এর quantity, serial, উৎস নথি ও entry user যাচাই করুন।',
                'স্টক অমিল হলে ধারাবাহিক movement দেখে কোন নথিতে পরিবর্তন হয়েছে শনাক্ত করুন।',
            ],
            'notes' => [
                'এটি অডিট তালিকা; মূল সংশোধন সংশ্লিষ্ট Purchase, Sale, Return বা Transfer নথিতে করুন।',
                'Serial ও non-serial quantity আলাদাভাবে মিলিয়ে দেখুন।',
            ],
        ],
        'warehouse-transfers.*' => [
            'title' => 'গুদাম স্টক ট্রান্সফার',
            'purpose' => 'এক গুদাম থেকে অন্য গুদামে এক বা একাধিক পণ্য এবং নির্দিষ্ট serial স্থানান্তর করুন।',
            'features' => [
                'From ও To warehouse নির্বাচন করে একাধিক transfer item যোগ করুন।',
                'Serial-tracked পণ্যে উৎস গুদামে থাকা নির্দিষ্ট serial নির্বাচন করুন।',
                'Quantity, serial count, transfer date ও note যাচাই করে একসঙ্গে সংরক্ষণ করুন।',
            ],
            'notes' => [
                'উৎস ও গন্তব্য একই হতে পারবে না এবং পর্যাপ্ত stock থাকা আবশ্যক।',
                'সংরক্ষণ করলে উভয় গুদামের stock ও movement history তাৎক্ষণিক বদলাবে।',
            ],
        ],
        'warehouses.*' => [
            'title' => 'গুদাম',
            'purpose' => 'প্রতিটি গুদামের পরিচয়, বর্তমান পণ্য/serial stock ও সাম্প্রতিক মুভমেন্ট পর্যবেক্ষণ করুন।',
            'features' => [
                'গুদামের নাম, code, ঠিকানা, default ও active অবস্থা পরিচালনা করুন।',
                'গুদামভিত্তিক পণ্যের মোট quantity এবং serial stock দেখুন।',
                'সাম্প্রতিক In/Out/Transfer ইতিহাস থেকে stock পরিবর্তনের উৎস শনাক্ত করুন।',
            ],
            'notes' => [
                'Default warehouse নতুন stock transaction-এ আগে নির্বাচিত হতে পারে।',
                'গুদাম নিষ্ক্রিয় করার আগে সেখানে থাকা stock ও চলমান transfer যাচাই করুন।',
            ],
        ],
        'product-categories.*' => [
            'title' => 'পণ্য ক্যাটাগরি',
            'purpose' => 'Inventory ও Purchase Bill-এ ব্যবহারের জন্য parent-child কাঠামোয় পণ্যের স্থায়ী শ্রেণিবিন্যাস।',
            'features' => [
                'Parent category নির্বাচন করে নতুন category বা subcategory যোগ করুন।',
                'Category tree দেখে পণ্যের শ্রেণিবিন্যাস ও hierarchy যাচাই করুন।',
                'পণ্য তৈরি বা purchase entry-এর আগে প্রয়োজনীয় category প্রস্তুত রাখুন।',
            ],
            'notes' => [
                'একই অর্থের duplicate category তৈরি না করে বিদ্যমান tree আগে খুঁজুন।',
                'ক্যাটাগরি কাঠামো রিপোর্ট ও পণ্য নির্বাচনে ব্যবহৃত হয়—নাম সংক্ষিপ্ত ও পরিষ্কার রাখুন।',
            ],
        ],
        'purchase-bills.*' => [
            'title' => 'ক্রয় বিল',
            'purpose' => 'Vendor/wholesale ক্রয়, পণ্যের stock entry, serial, warranty এবং ক্রয়মূল্য নথিভুক্ত করুন।',
            'features' => [
                'Vendor, bill no/date, payment তথ্য, document এবং একাধিক product line যোগ করুন।',
                'প্রতিটি পণ্যের quantity, unit cost, warehouse, serial ও warranty detail লিখুন।',
                'Draft যাচাই করে Finalize করুন এবং পণ্য stock ও ইতিহাসে সঠিক প্রভাব নিশ্চিত করুন।',
            ],
            'notes' => [
                'একই vendor bill number দ্বিতীয়বার ব্যবহার বা একই serial পুনরায় entry করবেন না।',
                'Finalize করার আগে quantity, cost, warehouse এবং serial count অবশ্যই মিলিয়ে নিন।',
            ],
        ],
        'products.*' => [
            'title' => 'পণ্য ও ইনভেন্টরি',
            'purpose' => 'ISP/কম্পিউটার পণ্য, মূল্য, warranty, serial tracking এবং গুদামভিত্তিক stock পরিচালনা করুন।',
            'features' => [
                'নাম, category, SKU, ক্রয়/বিক্রয় মূল্য, unit, warranty ও serial tracking নিয়ম রাখুন।',
                'গুদামভিত্তিক stock, serial status, বিক্রয়/ক্রয় reference ও movement দেখুন।',
                'অনুমোদিত adjustment-এর মাধ্যমে stock বাড়ানো/কমানো বা transfer পেজে যান।',
            ],
            'notes' => [
                'Serial tracking নিয়ম বদলানোর আগে বিদ্যমান stock ও serial record মিলিয়ে নিন।',
                'সরাসরি stock adjustment শুধু বাস্তব সংশোধনের জন্য ব্যবহার করুন এবং পরিষ্কার note দিন।',
            ],
        ],
        'warranty-claims.*' => [
            'title' => 'ওয়ারেন্টি ক্লেইম',
            'purpose' => 'বিক্রি হওয়া serial-এর সমস্যা, গ্রহণ, পরীক্ষা, repair, replacement, vendor return ও paid service ট্র্যাক করুন।',
            'features' => [
                'সম্ভব হলে sold serial নির্বাচন করে পার্টি ও পণ্যের ইতিহাসসহ claim তৈরি করুন।',
                'বর্তমান job status, customer problem, technician note ও প্রতিটি update নথিভুক্ত করুন।',
                'Replacement product, vendor action বা paid service invoice-এর ফলাফল সংরক্ষণ করুন।',
            ],
            'notes' => [
                'Replacement-এর serial ও stock source বাস্তব হস্তান্তরের সঙ্গে মিলিয়ে নিন।',
                'Status পরিবর্তনের প্রতিটি ধাপ customer communication ও audit history-তে গুরুত্বপূর্ণ।',
            ],
        ],
        'users.*' => [
            'title' => 'সিস্টেম ব্যবহারকারী',
            'purpose' => 'লগইন অ্যাকাউন্ট, ভূমিকা এবং ব্যবহারকারী-নির্দিষ্ট সরাসরি permission পরিচালনা করুন।',
            'features' => [
                'নাম, email, password ও active status দিয়ে ব্যবহারকারী তৈরি বা সম্পাদনা করুন।',
                'এক বা একাধিক Role দিয়ে সাধারণ permission group বরাদ্দ করুন।',
                'ব্যতিক্রমী প্রয়োজন হলে Direct Permission দিয়ে অতিরিক্ত নির্দিষ্ট অধিকার দিন।',
            ],
            'notes' => [
                'প্রয়োজনের চেয়ে বেশি permission দেবেন না; least-privilege নীতি অনুসরণ করুন।',
                'ব্যবহারকারী নিষ্ক্রিয় করলে তার আগের entry/audit history মুছে যায় না।',
            ],
        ],
        'roles.*' => [
            'title' => 'Role ও Permission',
            'purpose' => 'একই দায়িত্বের ব্যবহারকারীদের জন্য পুনর্ব্যবহারযোগ্য permission group তৈরি ও পরিচালনা করুন।',
            'features' => [
                'Role-এর নাম ও কাজের পরিধি অনুযায়ী প্রয়োজনীয় permission নির্বাচন করুন।',
                'Role তালিকা থেকে কোন group-এ কী access আছে তা পর্যালোচনা করুন।',
                'Role সম্পাদনা করে সংশ্লিষ্ট সব ব্যবহারকারীর অনুমতি একসঙ্গে হালনাগাদ করুন।',
            ],
            'notes' => [
                'Role পরিবর্তন করলে সেই Role পাওয়া সব ব্যবহারকারীর access বদলাতে পারে।',
                'Admin-level, backup ও আর্থিক permission দেওয়ার আগে অনুমোদন নিশ্চিত করুন।',
            ],
        ],
        'resellers.*' => [
            'title' => 'রিসেলার ব্যবস্থাপনা',
            'purpose' => 'রিসেলার পার্টির prepaid wallet, commission, দৈনিক সীমা, নির্ধারিত গ্রাহক ও বকেয়া পর্যবেক্ষণ করুন।',
            'features' => [
                'রিসেলারভিত্তিক wallet balance, customer count, unpaid bills ও commission দেখুন।',
                'পার্টির বিস্তারিত পেজ থেকে রিসেলার সেটিং ও গ্রাহক assignment পরিচালনা করুন।',
                'Top-up, customer payment ও commission পরিবর্তনের audit trail যাচাই করুন।',
            ],
            'notes' => [
                'রিসেলার wallet ব্যালেন্স সাধারণ customer advance থেকে আলাদা নিয়মে ব্যবহৃত হতে পারে।',
                'Commission বা limit পরিবর্তনের আগে কার্যকর তারিখ ও ব্যবসায়িক অনুমোদন নিশ্চিত করুন।',
            ],
        ],
        'troubleshoot.*' => [
            'title' => 'নেটওয়ার্ক ট্রাবলশুট',
            'purpose' => 'রাউটার থেকে আসা PPP disconnect webhook log এবং live MikroTik তথ্য দেখে সংযোগ-সমস্যার কারণ খুঁজে বের করার মডিউল।',
            'features' => [
                'ঘন ঘন disconnect, বারবার device MAC বদল এবং প্রতি-ইউজার disconnect summary রিপোর্ট দেখুন।',
                'ONU-এর Rx/Tx optical power এবং সর্বশেষ disconnect reason পার্টির পাশে মিলিয়ে নিন।',
                'Live Router Data পেজে চালু রাউটারের /print তথ্য এবং Webhook Settings নিয়ন্ত্রণ করুন।',
            ],
            'notes' => [
                'এই পেজগুলো read-only রিপোর্ট; রাউটারে কোনো পরিবর্তন করে না।',
                'Disconnect log না এলে Webhook Settings-এ script push এবং retention ঠিক আছে কি না দেখুন।',
            ],
        ],
    ],

    // More specific page content overrides the module/mode content above.
    'pages' => [
        'customers.payments.create' => [
            'title' => 'পার্টির ইনভয়েস পেমেন্ট রেকর্ড',
            'intro' => 'নির্বাচিত পার্টির unpaid/partial ইনভয়েসে সংগ্রহ করা টাকা বরাদ্দ এবং পেমেন্ট অ্যাকাউন্টে পোস্ট করুন।',
            'features' => [
                'পার্টির বর্তমান বকেয়া, অগ্রিম ব্যালেন্স ও পরিশোধযোগ্য ইনভয়েস দেখুন।',
                'ক্যাশ/ব্যাংক/MFS অ্যাকাউন্ট, তারিখ, পদ্ধতি, amount ও reference লিখুন।',
                'নির্বাচিত ইনভয়েসে পুরো বা আংশিক টাকা প্রয়োগ করে নতুন due amount তৈরি করুন।',
            ],
            'steps' => [
                'শিরোনামে পার্টির নাম এবং নিচে নির্বাচিত ইনভয়েসের নম্বর ও due মিলিয়ে নিন।',
                'প্রকৃত লেনদেন অনুযায়ী account, method, date, amount এবং reference পূরণ করুন।',
                'Submit করার পর Payment Details ও Invoice Allocation-এ সঠিক amount পোস্ট হয়েছে যাচাই করুন।',
            ],
            'notes' => [
                'বকেয়ার বেশি টাকা নিলে অতিরিক্ত অংশ advance হিসেবে যাবে কি না পেজের হিসাব দেখে নিশ্চিত করুন।',
                'একই receipt/reference আগে এন্ট্রি হয়েছে কি না Payments তালিকায় খুঁজে নিন।',
            ],
        ],
        'customers.advance-payments.create' => [
            'title' => 'পার্টির অগ্রিম টাকা গ্রহণ',
            'intro' => 'কোনো নির্দিষ্ট ইনভয়েস ছাড়াই পার্টির account balance-এ অগ্রিম টাকা যোগ করুন, যা পরে বকেয়া বিলে প্রয়োগ করা যাবে।',
            'features' => [
                'বর্তমান advance balance এবং আগের balance transaction history দেখুন।',
                'পেমেন্ট অ্যাকাউন্ট, তারিখ, amount, method ও referenceসহ অগ্রিম গ্রহণ করুন।',
                'প্রয়োজনে উপলভ্য balance পরবর্তী unpaid invoice-এ প্রয়োগ করুন।',
            ],
            'steps' => [
                'পার্টি এবং বর্তমান advance balance নিশ্চিত করুন।',
                'বাস্তবে টাকা যে account-এ এসেছে সেটি এবং সঠিক amount/reference লিখুন।',
                'সংরক্ষণের পর Advance Balance History ও Payment account ledger দুটিতেই entry যাচাই করুন।',
            ],
            'notes' => [
                'Advance receipt-কে সরাসরি invoice payment হিসেবে আবার এন্ট্রি করবেন না।',
                'ব্যালেন্স প্রয়োগের আগে target invoice ও প্রয়োগের amount নিশ্চিত করুন।',
            ],
        ],
        'reseller.customers.payments.create' => [
            'title' => 'রিসেলার অগ্রিম থেকে গ্রাহকের বিল পরিশোধ',
            'intro' => 'নির্বাচিত গ্রাহকের বকেয়া ইনভয়েস রিসেলারের prepaid wallet থেকে পরিশোধ করুন।',
            'features' => [
                'রিসেলারের বর্তমান wallet, দৈনিক অবশিষ্ট limit ও invoice due তুলনা করুন।',
                'পুরো বা আংশিক payment amount নির্বাচন করে due কমান।',
                'পেমেন্টের পর wallet transaction ও invoice allocation একসঙ্গে যাচাই করুন।',
            ],
            'steps' => [
                'রিসেলার, customer ও invoice number ঠিক আছে কি না মিলিয়ে নিন।',
                'Wallet balance এবং daily limit-এর মধ্যে payment amount লিখুন।',
                'Confirm করার পর invoice due এবং reseller wallet উভয়টি সঠিক পরিমাণে কমেছে দেখুন।',
            ],
            'notes' => [
                'এই payment-এর জন্য আলাদা cash/bank collection entry দেবেন না।',
                'ভুল customer বা invoice নির্বাচন করলে wallet ও billing history দুটোই প্রভাবিত হবে।',
            ],
        ],
        'in-house-use.index' => [
            'title' => 'কর্মচারীর জন্য In-house Issue তৈরি',
            'intro' => 'একই ফর্মে কর্মচারী, হস্তান্তর তথ্য এবং একাধিক নতুন/ব্যবহৃত stock item নির্বাচন করে সম্পদ ইস্যু করুন।',
            'features' => [
                'Employee, issue date, purpose, warehouse ও approval document নির্বাচন করুন।',
                'প্রতিটি row-তে new stock বা returned used stock source, product, quantity ও serial দিন।',
                'Issue Summary দেখে item count ও quantity মিলিয়ে একবারে assignment তৈরি করুন।',
            ],
            'steps' => [
                'প্রথমে কর্মচারী ও হস্তান্তরের তারিখ/কারণ পূরণ করুন।',
                'প্রয়োজনমতো item row যোগ করে source ও available stock অনুযায়ী quantity/serial নির্বাচন করুন।',
                'Summary ও approval evidence যাচাই করে Issue Items সংরক্ষণ করুন।',
            ],
            'notes' => [
                'Used returned item এবং new saleable item-এর source কখনো অদলবদল করবেন না।',
                'Serial count ও quantity সমান না হলে সংরক্ষণের আগে সংশোধন করুন।',
            ],
        ],
        'mikrotik-routers.compare' => [
            'title' => 'App ও Live MikroTik তুলনা/সিঙ্ক',
            'intro' => 'PPP Profile, IP Pool এবং PPPoE User-এর App record ও live router record পাশাপাশি দেখে নিয়ন্ত্রিত Import, Export বা Delete করুন।',
            'features' => [
                'Matched, App-only, MikroTik-only এবং তথ্যের অমিল row আলাদা করে শনাক্ত করুন।',
                'App authoritative হলে Export এবং router authoritative হলে Import ব্যবহার করুন।',
                'Inline edit দিয়ে mapping ঠিক করে পুনরায় তুলনা ও সিঙ্ক ফলাফল যাচাই করুন।',
            ],
            'steps' => [
                'Live data available এবং সঠিক router নির্বাচিত আছে নিশ্চিত করুন।',
                'Profiles, Pools ও PPPoE Users প্রতিটি section-এর difference ও source data পড়ুন।',
                'একবারে একটি নিয়ন্ত্রিত action নিন, তারপর refresh করে দুই পাশ মিলেছে কি না দেখুন।',
            ],
            'notes' => [
                'Bulk import/export-এর আগে backup এবং authoritative source নির্ধারণ করা জরুরি।',
                'Delete স্থায়ী live service disruption ঘটাতে পারে; confirmation ছাড়া ব্যবহার করবেন না।',
            ],
        ],
        'olt-onus.index' => [
            'title' => 'OLT ONU তালিকা ও Live Refresh',
            'intro' => 'সব OLT-এর cached/live ONU অবস্থা, PON port, optical signal ও operational summary এক জায়গায় দেখুন।',
            'features' => [
                'OLT, PON, status, serial/name বা signal condition দিয়ে ONU তালিকা ফিল্টার করুন।',
                'Live Refresh চালিয়ে progress, warning এবং শেষ refresh time পর্যবেক্ষণ করুন।',
                'ONU row খুলে VLAN, MAC, note, raw output ও interface configuration দেখুন।',
            ],
            'steps' => [
                'প্রয়োজনীয় OLT ও filter নির্বাচন করে বর্তমান cached ফলাফল দেখুন।',
                'সাম্প্রতিক তথ্য দরকার হলে Live Refresh চালিয়ে completion পর্যন্ত অপেক্ষা করুন।',
                'অস্বাভাবিক signal বা offline ONU-এর বিস্তারিত খুলে diagnosis data যাচাই করুন।',
            ],
            'notes' => [
                'Full refresh বড় OLT-এ সময় নিতে পারে; চলমান অবস্থায় একই refresh বারবার শুরু করবেন না।',
                'Raw command warning থাকলে তথ্য অসম্পূর্ণ হতে পারে—OLT profile/command পরীক্ষা করুন।',
            ],
        ],
        'network-map.index' => [
            'title' => 'FTTX Network Map ব্যবহার',
            'intro' => 'মাঠের অবকাঠামো সঠিক অবস্থানে বসিয়ে node ও fiber link-এর একটি ব্যবহারযোগ্য নেটওয়ার্ক মানচিত্র তৈরি করুন।',
            'features' => [
                'Location Search বা map navigation দিয়ে কাজের এলাকা কেন্দ্রে আনুন।',
                'Node/Link tool নির্বাচন করে প্রয়োজনীয় geometry আঁকুন ও বিস্তারিত ফর্ম পূরণ করুন।',
                'Layer visibility ও map style বদলে নির্দিষ্ট অবকাঠামো পরিষ্কারভাবে পর্যালোচনা করুন।',
            ],
            'steps' => [
                'প্রথমে map style, visible layer এবং zoom level কাজের উপযোগী করুন।',
                'নতুন feature আঁকুন অথবা বিদ্যমান feature নির্বাচন করে form-এর তথ্য যাচাই/সম্পাদনা করুন।',
                'Save-এর পর মানচিত্রে অবস্থান, label এবং link-এর দুই প্রান্ত সঠিক দেখাচ্ছে নিশ্চিত করুন।',
            ],
            'notes' => [
                'Link আঁকার সময় বাস্তব upstream/downstream node অনুসরণ করুন।',
                'মোবাইলে location permission দিলে বর্তমান অবস্থান ব্যবহার করা সহজ হয়।',
            ],
        ],
        'invoices.create|invoices.edit|quotations.create|quotations.edit' => [
            'title' => 'ইনভয়েস/কোটেশন ফর্ম',
            'intro' => 'Party, document details এবং line item-এর সব হিসাব যাচাই করে নতুন বিক্রয় নথি তৈরি বা বিদ্যমান Draft সংশোধন করুন।',
            'features' => [
                'Document type অনুযায়ী party, billing/date, due date, warehouse ও payment note দিন।',
                'পণ্য/সেবা row-তে quantity, unit price, serial, discount এবং VAT পূরণ করুন।',
                'Summary-তে subtotal, discount, VAT, reseller commission, total ও due তাৎক্ষণিক মিলান।',
            ],
            'steps' => [
                'সঠিক document type ও party নির্বাচন করে উপরের mandatory details পূরণ করুন।',
                'প্রতিটি line item-এর stock/serial, quantity এবং rate যাচাই করে প্রয়োজনীয় note যোগ করুন।',
                'ডান পাশের Summary মিলিয়ে Save করুন; Finalize আলাদা হলে preview যাচাই করে পরে করুন।',
            ],
            'notes' => [
                'Serial-tracked পণ্যের serial অন্য বিক্রয়ে ব্যবহৃত হয়নি নিশ্চিত করুন।',
                'Edit শুধু অনুমোদিত Draft-এ করুন; চূড়ান্ত আর্থিক নথি পরিবর্তনের বদলে return/adjustment ব্যবহার করুন।',
            ],
        ],
        'fleet.maintenance.schedules' => [
            'title' => 'Maintenance Schedule ও Due নিয়ন্ত্রণ',
            'intro' => 'প্রতিটি যানবাহনের periodic check/change/service interval সেট করে upcoming, due এবং overdue কাজ দেখুন।',
            'features' => [
                'Vehicle, maintenance item, day interval বা mileage interval দিয়ে schedule তৈরি করুন।',
                'Last service থেকে next due date/km এবং বর্তমান remaining/overdue অবস্থা দেখুন।',
                'Due item থেকে সরাসরি maintenance log তৈরি করে schedule cycle হালনাগাদ করুন।',
            ],
            'steps' => [
                'যানবাহন ও কাজের ধরন বেছে বাস্তব manufacturer/service interval লিখুন।',
                'তালিকার due status নিয়মিত দেখে প্রয়োজনীয় কাজ পরিকল্পনা করুন।',
                'কাজ সম্পন্ন হলে সঠিক date ও mileageসহ log করুন, যাতে next due পুনর্গণনা হয়।',
            ],
            'notes' => [
                'দিন ও mileage উভয় interval থাকলে যেটি আগে due হয় সেটিকে অগ্রাধিকার দিন।',
                'ভুল last service data ভবিষ্যৎ সব reminder ভুল করতে পারে।',
            ],
        ],
        'warranty-claims.show' => [
            'title' => 'ওয়ারেন্টি ক্লেইম কাজের পেজ',
            'intro' => 'একটি claim গ্রহণ থেকে সমাপ্তি পর্যন্ত বর্তমান কাজ, technician update, replacement/service এবং পূর্ণ history পরিচালনা করুন।',
            'features' => [
                'Customer problem, sold asset, warranty eligibility ও current job status একসঙ্গে দেখুন।',
                'Quick Action বা Manual Update দিয়ে প্রতিটি বাস্তব অগ্রগতি time-stamped history-তে যোগ করুন।',
                'Replacement serial/stock বা paid service invoice যুক্ত করে final outcome নথিভুক্ত করুন।',
            ],
            'steps' => [
                'Claim number, customer, product/serial ও warranty condition যাচাই করুন।',
                'বাস্তব কাজ অনুযায়ী status এবং বিস্তারিত note দিয়ে update সংরক্ষণ করুন।',
                'হস্তান্তর/সমাপ্তির আগে replacement বা service তথ্য এবং Work History মিলিয়ে নিন।',
            ],
            'notes' => [
                'Customer-কে না জানিয়ে status completed/returned করবেন না।',
                'Replacement দিলে নির্বাচিত serial-এর stock movement ও warranty history তৈরি হবে।',
            ],
        ],

        'router-users.index' => [
            'title' => 'রাউটার ইউজার (App-এ নেই)',
            'intro' => 'সব চালু MikroTik রাউটার থেকে আনা PPPoE secret ও live session এক তালিকায় দেখুন; কোনটি App পার্টির সঙ্গে মিলেছে আর কোনটি শুধু রাউটারে আছে তা শনাক্ত করুন।',
            'features' => [
                'প্রতিটি row-তে “✓ Linked”, “✓ Name match” বা “Not in app” অবস্থা, device MAC, remote address, profile ও status দেখুন।',
                'Refresh secrets এবং shared password দিয়ে Pull active connections চালিয়ে তালিকা ও party device MAC হালনাগাদ করুন।',
                '“Not in app” row নির্বাচন করে পার্টি তৈরি করুন, অথবা প্রতি-row Delete from MikroTik দিয়ে বাতিল secret মুছুন।',
            ],
            'steps' => [
                'প্রয়োজনে উপরে থেকে নির্দিষ্ট রাউটার ফিল্টার করে তালিকা ছোট করুন।',
                'তথ্য পুরনো মনে হলে Refresh secrets, আর MAC/IP দরকার হলে Pull active connections চালান।',
                'Unmanaged row-গুলো টিক দিয়ে party import করুন অথবা রাউটার থেকে মুছে তালিকা পরিষ্কার রাখুন।',
            ],
            'notes' => [
                'Refresh secrets চালালে নিষ্ক্রিয়/মুছে ফেলা রাউটারের পুরনো row স্বয়ংক্রিয়ভাবে বাদ যায়।',
                'Delete from MikroTik সরাসরি live secret মুছে দেয় ও চলমান session বন্ধ করে; party-linked বা read-only রাউটারে কাজ করবে না।',
            ],
        ],
        'troubleshoot.webhook.edit' => [
            'title' => 'PPP Disconnect Webhook সেটিং',
            'intro' => 'প্রতিটি চালু রাউটারের সব PPP profile-এ একটি শেয়ার্ড on-down script বসিয়ে session drop হলে usage তথ্য App-এ POST করানোর ব্যবস্থা।',
            'features' => [
                'Webhook চালু/বন্ধ করুন এবং Save করলে সব রাউটারে script push বা মুছে দিন।',
                'Callback URL ও গোপন secret দেখুন; script রাউটারে $"last-disconnect-reason"সহ usage পাঠায়।',
                'Disconnect-log retention (দিন) ঠিক করুন এবং প্রয়োজনে পুরনো row এখনই মুছুন।',
            ],
            'steps' => [
                'URL ও secret ঠিক আছে যাচাই করে Webhook enable করুন।',
                '“Save & push to all routers” চাপুন; ফলাফল বার্তায় কয়টি profile-এ লেখা হলো দেখুন।',
                'Retention দিন সেট করে সংরক্ষণ করুন, দরকার হলে “Delete old rows now” চালান।',
            ],
            'notes' => [
                'Script পরিবর্তনের পর নতুন করে Save না করলে পুরনো on-down রাউটারেই থেকে যায়।',
                'Retention 0 রাখলে disconnect log কখনো মুছবে না; বড় ডেটাবেজে বুঝে সেট করুন।',
            ],
        ],
        'troubleshoot.frequent-disconnects' => [
            'title' => 'ঘন ঘন Disconnect রিপোর্ট',
            'intro' => 'নির্বাচিত সময়-জানালায় যেসব ইউজারের PPP session নির্দিষ্ট সংখ্যক বারের বেশি drop হয়েছে তাদের তালিকা।',
            'features' => [
                'Window (ঘণ্টা), সর্বনিম্ন disconnect সংখ্যা এবং রাউটার দিয়ে ফিল্টার করুন।',
                'প্রতিটি ইউজারের disconnect সংখ্যা, রাউটার, ONU Rx/Tx power ও সর্বশেষ disconnect reason দেখুন।',
                '“Make default” দিয়ে বর্তমান ফিল্টার এই পেজের ডিফল্ট হিসেবে সংরক্ষণ করুন।',
            ],
            'steps' => [
                'সন্দেহজনক সময়সীমা ও threshold অনুযায়ী Window ও Min disconnects দিন।',
                'Apply চেপে বেশি drop হওয়া লাইনগুলো তালিকায় দেখুন।',
                'Party নামে ক্লিক করে গ্রাহকের বিস্তারিত পেজে যান এবং ONU power/reason মিলিয়ে ব্যবস্থা নিন।',
            ],
            'notes' => [
                'তথ্য আসে on-down webhook থেকে; webhook বন্ধ থাকলে বা retention কম হলে তালিকা অসম্পূর্ণ হতে পারে।',
                'Rx power −25 dBm-এর নিচে বা reason-এ timeout/auth থাকলে fiber বা রাউটার সমস্যা সন্দেহ করুন।',
            ],
        ],
        'troubleshoot.mac-changes' => [
            'title' => 'বারবার Device MAC বদল রিপোর্ট',
            'intro' => 'নির্বাচিত সময়-জানালায় যেসব ইউজার একাধিক আলাদা device MAC থেকে সংযোগ নিয়েছে তাদের তালিকা — router বদল, লাইন শেয়ার বা MAC spoofing ধরার জন্য।',
            'features' => [
                'Window (ঘণ্টা), সর্বনিম্ন আলাদা MAC সংখ্যা এবং রাউটার দিয়ে ফিল্টার করুন।',
                'প্রতিটি ইউজারের আলাদা MAC সংখ্যা, event count, সাম্প্রতিক MAC তালিকা, ONU power ও disconnect reason দেখুন।',
                '“Make default” দিয়ে ফিল্টার সংরক্ষণ এবং Reset দিয়ে আগের অবস্থায় ফিরুন।',
            ],
            'steps' => [
                'যাচাইয়ের সময়সীমা ও সর্বনিম্ন MAC সংখ্যা দিন (সাধারণত ৩+)।',
                'Apply চেপে তালিকা দেখুন এবং প্রতিটি row-র সাম্প্রতিক MAC-গুলো পরীক্ষা করুন।',
                'একই সময়ে বহু MAC হলে গ্রাহকের সঙ্গে যোগাযোগ করে router/সংযোগ যাচাই করুন।',
            ],
            'notes' => [
                'caller-id-তে device MAC না থাকলে (কিছু PPP সেবা IP দেয়) সেই session এখানে গোনা হয় না।',
                'বৈধভাবে একাধিক ডিভাইস থাকলেও উচ্চ সংখ্যা দেখা যেতে পারে; সিদ্ধান্তের আগে যাচাই করুন।',
            ],
        ],
        'troubleshoot.analytics' => [
            'title' => 'Connection Analytics',
            'intro' => 'disconnect log-এ থাকা প্রতিটি ইউজারের জন্য 24 ঘণ্টা / 7 দিন / 30 দিন / সর্বমোট disconnect সংখ্যার এক-নজর সারসংক্ষেপ।',
            'features' => [
                'যেকোনো কলাম (24h, 7d, 30d, সর্বমোট, শেষ disconnect) ধরে sort করুন।',
                'Username দিয়ে খুঁজুন এবং নির্দিষ্ট রাউটারে তালিকা সীমিত করুন।',
                'প্রতিটি row-তে ONU Rx/Tx power ও সর্বশেষ disconnect reason দেখে সমস্যাযুক্ত গ্রাহক চিহ্নিত করুন।',
            ],
            'steps' => [
                'প্রয়োজনে search ও router ফিল্টার দিন।',
                'সর্বমোট বা 24h কলামে ক্লিক করে সবচেয়ে বেশি disconnect হওয়া ইউজার উপরে আনুন।',
                'সন্দেহজনক লাইনে party খুলে ONU power ও reason অনুযায়ী ব্যবস্থা নিন।',
            ],
            'notes' => [
                'এই সংখ্যা শুধু on-down webhook থেকে আসা row-এর ভিত্তিতে; retention window-এর বাইরের ডেটা এখানে নেই।',
                'Sort ও filter “Make default” দিয়ে সংরক্ষণ করলে পরেরবার সেভাবেই খুলবে।',
            ],
        ],
        'troubleshoot.router-data' => [
            'title' => 'Router Live Data',
            'intro' => 'সব চালু MikroTik রাউটার থেকে read-only /print তথ্য (log, system resource, interface, PPP active, IP/queue/firewall ইত্যাদি) live টেনে এনে রাউটার-অনুযায়ী দেখানো হয়।',
            'features' => [
                'প্রতিটি রাউটারের সেকশনগুলো আলাদা কার্ডে খোলা/বন্ধ করে দেখুন; Recent log প্রথমে ও খোলা থাকে।',
                'uptime দিনে, cpu-load %-এ, memory GiB/MiB-তে সহজপাঠ্য করে দেখানো হয়; কাঁচা মান tooltip-এ থাকে।',
                'নিচের কমান্ড বক্সে /print দিয়ে শেষ হওয়া যেকোনো path লিখে সব রাউটারে চালান।',
            ],
            'steps' => [
                'পেজ খুললে সব চালু রাউটার থেকে একসাথে ডেটা টানে; উপরে শেষ fetch সময় দেখা যায়।',
                'দরকারি সেকশনের summary/rows দেখুন; কোনো রাউটার unreachable হলে ঐ সেকশনে error দেখাবে, বাকিগুলো আসবে।',
                'বাড়তি তথ্য দরকার হলে কমান্ড বক্সে path লিখে “Run on all routers” চাপুন, শেষে Refresh দিন।',
            ],
            'notes' => [
                'শুধু /print (read-only) path চলে; লেখার কমান্ড এখান থেকে চালানো যায় না।',
                'অনেক রাউটার বা বড় টেবিল হলে প্রথম লোডে কয়েক সেকেন্ড লাগতে পারে; প্রতি সেকশনে সর্বোচ্চ ৫০০ row দেখানো হয়।',
            ],
        ],
        'troubleshoot.onu-signal' => [
            'title' => 'ONU Signal History (সব পার্টি)',
            'intro' => 'যেসব পার্টির ONU Rx/Tx optical power সংরক্ষিত আছে, তাদের প্রত্যেকের সিগন্যাল গ্রাফ একই পেজে দেখানো হয়। সবুজ ব্যান্ড −১৫ থেকে −২৫ dBm (স্বাভাবিক)।',
            'features' => [
                'উপরের একটিমাত্র Rx / Tx সুইচ পেজের সব গ্রাফে একসাথে প্রযোজ্য হয় এবং পছন্দটি সংরক্ষিত থাকে।',
                'প্রতিটি গ্রাফে hover করলে ঐ সময়ের তারিখ ও Rx/Tx মান দেখা যায়; Zoom স্লাইডারে টাইমলাইন বড় করে দেখা যায়।',
                'কত দিন ও কত ঘণ্টা অন্তর নমুনা রাখা হবে তা OLT ONUs পেজের সেটিংস থেকে নিয়ন্ত্রিত।',
            ],
            'steps' => [
                'পেজ খুলুন — নাম অনুযায়ী সাজানো পার্টিগুলো পৃষ্ঠা আকারে (২০টি করে) আসে।',
                'উপরের Rx/Tx টিক দিয়ে দরকারি সিরিজ চালু/বন্ধ করুন; পেজ রিলোড হয় না।',
                'কোনো পার্টির নামে ক্লিক করে সেই পার্টির বিস্তারিত পেজে যান।',
            ],
            'notes' => [
                'নমুনা প্রতি নির্ধারিত ঘণ্টায় background job সংগ্রহ করে; নতুন পার্টির অন্তত ১টি নমুনা জমা হলে এখানে দেখা যাবে।',
                'নির্ধারিত retention দিনের পুরনো নমুনা স্বয়ংক্রিয়ভাবে মুছে যায়।',
            ],
        ],
        'concession-reports.index' => [
            'title' => 'কনসেশন / ছাড় রিপোর্ট — বিস্তারিত',
            'intro' => 'গ্রেস পিরিয়ড, force-active, বিশেষ ISP flag ও অন্যান্য ছাড়ের প্রতিটি ঘটনা সময় অনুযায়ী, টাকার মূল্যসহ দেখুন।',
            'features' => [
                'কোন admin কবে কোন পার্টিকে কী ছাড় দিয়েছেন এবং তার আনুমানিক টাকার মূল্য দেখুন।',
                'এখনো চলমান (open) ছাড়ের বেড়ে চলা মূল্যও মোট হিসাবে ধরা হয়।',
                'তারিখ/পার্টি/ধরন অনুযায়ী ফিল্টার করে নির্দিষ্ট সময়ের give-away বের করুন।',
            ],
            'steps' => [
                'প্রয়োজনীয় তারিখ পরিসর ও ফিল্টার নির্বাচন করুন।',
                'তালিকা ও মোট মূল্য দেখে অস্বাভাবিক বা বড় ছাড় চিহ্নিত করুন।',
                'দরকার হলে Summary ট্যাবে গিয়ে admin-ভিত্তিক মোট মিলিয়ে নিন।',
            ],
            'notes' => [
                'টাকার অঙ্ক আনুমানিক প্যাকেজ মূল্যের ভিত্তিতে হিসাব করা; হুবহু বিলিং নয়।',
                'Open concession চলতে থাকলে তার মূল্য পরবর্তী রিপোর্টে বাড়তে পারে।',
            ],
        ],
        'concession-reports.summary' => [
            'title' => 'কনসেশন / ছাড় রিপোর্ট — সারসংক্ষেপ',
            'intro' => 'প্রতি admin ও প্রতি action অনুযায়ী মোট কতবার ছাড় দেওয়া হয়েছে এবং মোট কত টাকার give-away হয়েছে তার সংক্ষিপ্ত হিসাব।',
            'features' => [
                'Admin-ভিত্তিক bucket-এ action count ও মোট টাকা এক নজরে দেখুন।',
                'চলমান open concession-এর বেড়ে চলা মূল্য নিজ নিজ bucket-এ যুক্ত থাকে।',
                'নির্দিষ্ট সময়সীমা দিয়ে কোন period-এ সবচেয়ে বেশি ছাড় হয়েছে তুলনা করুন।',
            ],
            'steps' => [
                'তারিখ পরিসর নির্বাচন করুন।',
                'Admin ও action অনুযায়ী মোট দেখে দায়িত্ব ও প্রবণতা বুঝুন।',
                'কোনো লাইন সন্দেহজনক হলে বিস্তারিত ট্যাবে গিয়ে আলাদা ঘটনা যাচাই করুন।',
            ],
            'notes' => [
                'এটি নীতিগত পর্যবেক্ষণের জন্য; হুবহু আর্থিক লেজার নয়।',
                'একই পার্টিতে একাধিক ধরনের ছাড় থাকলে প্রতিটি আলাদা bucket-এ গোনা হয়।',
            ],
        ],
        'account-deposits.create' => [
            'title' => 'অফিসে অ্যাকাউন্টের টাকা জমা',
            'intro' => 'একটি পেমেন্ট অ্যাকাউন্টে জমে থাকা টাকা অফিসে হস্তান্তরের রেকর্ড তৈরি করুন, যাতে অ্যাকাউন্ট ব্যালেন্স ঠিক থাকে।',
            'features' => [
                'অ্যাকাউন্টের বর্তমান ব্যালেন্স দেখে জমা দেওয়ার amount, তারিখ ও note দিন।',
                'জমার entry অ্যাকাউন্ট ledger-এ debit হিসেবে বসে ব্যালেন্স কমায়।',
                'কে জমা দিলেন তা রেকর্ডে থাকে; শুধু অ্যাকাউন্ট owner বা super admin এটি করতে পারেন।',
            ],
            'steps' => [
                'সঠিক পেমেন্ট অ্যাকাউন্ট এবং তার বর্তমান ব্যালেন্স যাচাই করুন।',
                'বাস্তবে অফিসে দেওয়া টাকার পরিমাণ, তারিখ ও রেফারেন্স/নোট লিখুন।',
                'সংরক্ষণের পর অ্যাকাউন্ট ledger-এ entry ও নতুন ব্যালেন্স মিলিয়ে নিন।',
            ],
            'notes' => [
                'ব্যালেন্সের চেয়ে বেশি amount জমা দেখাবেন না; আগে হিসাব মিলিয়ে নিন।',
                'ভুল amount দিলে ledger ও cash reconciliation দুটোই ভুল হবে।',
            ],
        ],
        'payment-account-access.index' => [
            'title' => 'পেমেন্ট অ্যাকাউন্ট অ্যাক্সেস',
            'intro' => 'কোন ব্যবহারকারী কোন পেমেন্ট অ্যাকাউন্টে টাকা নিতে/জমা দিতে পারবেন তা নির্ধারণ করুন।',
            'features' => [
                'প্রতিটি অ্যাকাউন্টের owner এবং অতিরিক্ত অনুমোদিত (delegate) ব্যবহারকারীদের তালিকা দেখুন।',
                'ব্যবহারকারী যোগ/বাদ দিয়ে অ্যাকাউন্টে কে entry করতে পারবেন তা ঠিক করুন।',
                'পরিবর্তন সঙ্গে সঙ্গে payment ফর্মের অ্যাকাউন্ট তালিকায় প্রতিফলিত হয়।',
            ],
            'steps' => [
                'যে অ্যাকাউন্টের অ্যাক্সেস বদলাবেন সেটি খুঁজুন।',
                'প্রয়োজনীয় ব্যবহারকারী নির্বাচন করে সংরক্ষণ করুন।',
                'সংশ্লিষ্ট ব্যবহারকারীর payment ফর্মে অ্যাকাউন্টটি আসছে/যাচ্ছে কি না যাচাই করুন।',
            ],
            'notes' => [
                'Owner সবসময় নিজের অ্যাকাউন্টে অ্যাক্সেস পান; তাকে আলাদা delegate করার দরকার নেই।',
                'অ্যাক্সেস তুলে নিলে ঐ ব্যবহারকারীর পুরনো entry মুছে যায় না, শুধু নতুন entry বন্ধ হয়।',
            ],
        ],
    ],

    // Page type guides. Specific routes must stay above generic suffix patterns.
    'modes' => [
        'login' => [
            'title' => 'প্রবেশ পদ্ধতি',
            'description' => 'সঠিক পরিচয় দিয়ে লগইন করুন।',
            'steps' => [
                'Email ঘরে আপনার সিস্টেমে নিবন্ধিত ইমেইল লিখুন।',
                'Password লিখে ব্যক্তিগত ডিভাইস হলে প্রয়োজনমতো “Remember me” নির্বাচন করুন।',
                'Login চাপুন; ত্রুটি দেখালে তথ্য সংশোধন করে আবার চেষ্টা করুন।',
            ],
        ],
        'dashboard|reseller.dashboard' => [
            'title' => 'সারসংক্ষেপ দেখা',
            'description' => 'প্রথমে সারসংক্ষেপ দেখে যেটির বিস্তারিত দরকার সেই card, invoice বা ticket খুলুন।',
            'steps' => [
                'উপরের statistic card থেকে অস্বাভাবিক বকেয়া বা পরিবর্তন শনাক্ত করুন।',
                'সাম্প্রতিক তালিকায় প্রয়োজনীয় record খুঁজে তার বিস্তারিত পেজ খুলুন।',
                'পূর্ণ তালিকা বা নতুন কাজের জন্য উপরের navigation menu ব্যবহার করুন।',
            ],
        ],
        'fleet.reports|fleet.reports.*|in-house-use.report.*' => [
            'title' => 'রিপোর্ট দেখা',
            'description' => 'ফিল্টার, সারসংক্ষেপ ও itemized তথ্য ব্যবহার করে নির্দিষ্ট সময় বা বিষয়ের রিপোর্ট বিশ্লেষণ করুন।',
            'steps' => [
                'প্রয়োজনীয় report type খুলে date range, entity এবং status filter দিন।',
                'Apply করে summary total এবং নিচের detail row পরস্পরের সঙ্গে মিলিয়ে দেখুন।',
                'Pagination বা per-page বদলে বাকি ফলাফল দেখুন এবং দরকার হলে source record খুলুন।',
            ],
        ],
        'accounting.ledger|payment-accounts.cash-ledger|employees.balance-sheet' => [
            'title' => 'লেজার/ব্যালেন্স দেখা',
            'description' => 'তারিখ ও সংশ্লিষ্ট entity দিয়ে ফিল্টার করে opening, debit, credit এবং running balance মিলান।',
            'steps' => [
                'Party/account/employee এবং প্রয়োজনীয় date range নির্বাচন করুন।',
                'Summary total-এর সঙ্গে প্রতিটি entry ও running balance ধারাবাহিকভাবে মিলান।',
                'অমিল entry-এর reference ধরে মূল transaction খুলুন বা print report তৈরি করুন।',
            ],
        ],
        '*.payment-note-default.edit|fleet.settings' => [
            'title' => 'সেটিং পরিবর্তন',
            'description' => 'বর্তমান default মান দেখে ভবিষ্যৎ রেকর্ড বা আচরণের জন্য প্রয়োজনীয় সেটিং সংরক্ষণ করুন।',
            'steps' => [
                'বর্তমান value এবং সেটিংটি কোথায় ব্যবহৃত হয় পড়ুন।',
                'প্রয়োজনীয় মান পরিবর্তন করে format/limit ঠিক আছে নিশ্চিত করুন।',
                'Save করার পর পেজ reload করে নতুন default কার্যকর হয়েছে যাচাই করুন।',
            ],
        ],
        'network-map.index' => [
            'title' => 'ইন্টার‌্যাক্টিভ ম্যাপ',
            'description' => 'Map tool ও side panel ব্যবহার করে location এবং network feature পরিচালনা করুন।',
            'steps' => [
                'Search/zoom দিয়ে এলাকা খুঁজে প্রয়োজনীয় layer দৃশ্যমান করুন।',
                'Tool নির্বাচন করে feature আঁকুন অথবা বিদ্যমান feature নির্বাচন করুন।',
                'বিস্তারিত form পূরণ করে Save এবং map-এ ফলাফল যাচাই করুন।',
            ],
        ],
        'mikrotik-routers.compare|mikrotik-routers.profiles.index|mikrotik-routers.pools.index|mikrotik-routers.imported-secrets.index|ip-pools.index' => [
            'title' => 'তুলনা ও সিঙ্ক',
            'description' => 'App record এবং network device snapshot-এর উৎস বুঝে নিয়ন্ত্রিত import/export করুন।',
            'steps' => [
                'সঠিক router এবং সাম্প্রতিক live snapshot নির্বাচন/refresh করুন।',
                'দুই পাশের নাম, range/profile/secret এবং status তুলনা করুন।',
                'প্রয়োজনীয় একক action নিয়ে ফলাফল refresh করে যাচাই করুন।',
            ],
        ],
        'olt-onus.deny-list|olt-onus.auto-discovery' => [
            'title' => 'OLT Utility তালিকা',
            'description' => 'OLT command থেকে আনা বিশেষ তালিকা ফিল্টার করুন এবং যাচাই করে সংশ্লিষ্ট ONU action নিন।',
            'steps' => [
                'OLT নির্বাচন করে সর্বশেষ command result refresh করুন।',
                'Serial, PON ও status দেখে target ONU নিশ্চিত করুন।',
                'Add/Remove action-এর পর তালিকা আবার refresh করে ফলাফল যাচাই করুন।',
            ],
        ],
        '*.voucher|*.thermal-voucher|*.print|*.invoice|*.delivery-challan|*.quotation' => [
            'title' => 'প্রিন্ট প্রিভিউ',
            'description' => 'প্রিন্টের আগে প্রতিষ্ঠান, design option এবং নথির সব তথ্য শেষবার যাচাই করুন।',
            'steps' => [
                'Organization, কাগজ/রঙ/স্বাক্ষর ও অন্যান্য print option নির্বাচন করুন।',
                'নথির নম্বর, party, তারিখ, item, amount এবং note স্ক্রিনে মিলিয়ে নিন।',
                'Print বাটন চাপুন; প্রিন্ট শেষে Back দিয়ে মূল record-এ ফিরে যান।',
            ],
        ],
        '*.index' => [
            'title' => 'তালিকা ও অনুসন্ধান',
            'description' => 'সার্চ, ফিল্টার, summary এবং pagination ব্যবহার করে প্রয়োজনীয় record খুঁজুন।',
            'steps' => [
                'Search/filter-এ পরিচিত তথ্য লিখে Apply বা Filter চাপুন।',
                'Summary এবং table row দেখে সঠিক record শনাক্ত করুন; দরকার হলে per-page বদলান।',
                'Row বা action button থেকে Details/Edit খুলুন অথবা Add/New দিয়ে নতুন record শুরু করুন।',
            ],
        ],
        '*.create' => [
            'title' => 'নতুন রেকর্ড তৈরি',
            'description' => 'আবশ্যক ও ঐচ্ছিক field পূরণ করে নতুন রেকর্ড সংরক্ষণ করুন।',
            'steps' => [
                'পেজের পরিচয় ও context দেখে সঠিক entity তৈরি করছেন নিশ্চিত করুন।',
                'Required field পূরণ করে date, amount, status ও reference ভালোভাবে মিলান।',
                'Save/Create চাপুন; validation error থাকলে দেখানো field ঠিক করে পুনরায় জমা দিন।',
            ],
        ],
        '*.edit' => [
            'title' => 'রেকর্ড সম্পাদনা',
            'description' => 'বর্তমান সংরক্ষিত মান দেখে শুধু প্রয়োজনীয় পরিবর্তন করুন এবং audit impact বুঝে Update করুন।',
            'steps' => [
                'শিরোনাম/পরিচয় দেখে সঠিক record খোলা হয়েছে নিশ্চিত করুন।',
                'বর্তমান value-এর সঙ্গে নতুন তথ্য মিলিয়ে শুধু প্রয়োজনীয় field পরিবর্তন করুন।',
                'Update/Save-এর পর details ও Edit History-তে পরিবর্তন সঠিক হয়েছে যাচাই করুন।',
            ],
        ],
        '*.show' => [
            'title' => 'বিস্তারিত ও কার্যক্রম',
            'description' => 'একটি record-এর সারসংক্ষেপ, সম্পর্কিত ইতিহাস এবং অনুমোদিত action এক জায়গায় দেখুন।',
            'steps' => [
                'উপরের পরিচয়, status এবং গুরুত্বপূর্ণ summary তথ্য যাচাই করুন।',
                'নিচের related section, transaction/history ও note ধারাবাহিকভাবে দেখুন।',
                'কোনো action নিলে তার প্রভাব বুঝে confirm করুন এবং ফলাফল/ইতিহাস যাচাই করুন।',
            ],
        ],
    ],
];
