plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
}

import java.util.Properties

android {
    namespace = "com.example.anonbtcwallet"
    compileSdk = 35

    defaultConfig {
        applicationId = "com.example.anonbtcwallet"
        minSdk = 28
        targetSdk = 35
        versionCode = 1
        versionName = "1.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    signingConfigs {
        create("release") {
            val props = Properties()
            val file = rootProject.file("local.properties")
            if (file.exists()) props.load(file.inputStream())
            storeFile = file(props["RELEASE_STORE_FILE"].toString())
            storePassword = props["RELEASE_STORE_PASSWORD"].toString()
            keyAlias = props["RELEASE_KEY_ALIAS"].toString()
            keyPassword = props["RELEASE_KEY_PASSWORD"].toString()
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
            signingConfig = signingConfigs.getByName("release")
        }
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }
    kotlinOptions {
        jvmTarget = "11"
    }
}

dependencies {

    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.appcompat)
    implementation(libs.material)
    implementation("com.squareup.okhttp3:okhttp:4.9.3")
    implementation("org.jsoup:jsoup:1.17.2")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")
    implementation("com.android.volley:volley:1.2.1")
    implementation("com.google.zxing:core:3.5.1")
    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)
    implementation("androidx.recyclerview:recyclerview:1.3.1")
    implementation ("com.google.android.material:material:1.9.0") // или актуальная версия

}

