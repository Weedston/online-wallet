package com.example.anonbtcwallet

import android.content.Context
import android.content.Intent
import android.content.res.Configuration
import android.os.Bundle
import android.view.Gravity
import android.view.LayoutInflater
import android.view.View
import android.widget.*
import androidx.appcompat.app.AppCompatActivity
import okhttp3.*
import org.json.JSONObject
import java.io.IOException
import java.util.Locale

class SplashActivity : AppCompatActivity() {
    private lateinit var welcomeTextView: TextView
    private lateinit var nextButton: Button
    private lateinit var langTriangle: ImageView

    private var lang: String = "ru"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_splash)

        welcomeTextView = findViewById(R.id.welcomeTextView)
        nextButton = findViewById(R.id.nextButton)
        langTriangle = findViewById(R.id.langTriangle)

        // Загружаем язык из настроек, если был выбран ранее
        val prefs = getSharedPreferences("auth", Context.MODE_PRIVATE)
        lang = prefs.getString("lang", "ru") ?: "ru"
        setAppLocale(lang) // установка локали

        fetchWelcomeText()

        nextButton.setOnClickListener {
            // Сохраняем выбранный язык
            prefs.edit().putString("lang", lang).apply()
            val sid = prefs.getString("sid", null)
            // Проверяем только SID
            if (!sid.isNullOrEmpty()) {
                startActivity(Intent(this, PinEnterActivity::class.java))
            } else {
                startActivity(Intent(this, PasswordActivity::class.java))
            }
            finish()
        }

        langTriangle.setOnClickListener { showLanguagePopup(it) }
    }

    private fun showLanguagePopup(anchor: View) {
        val popup = PopupMenu(this, anchor, Gravity.END)
        popup.menu.add(0, 1, 0, "Русский")
        popup.menu.add(0, 2, 1, "English")
        popup.setOnMenuItemClickListener { item ->
            val newLang = when (item.itemId) {
                1 -> "ru"
                2 -> "en"
                else -> lang
            }
            if (newLang != lang) {
                lang = newLang
                getSharedPreferences("auth", Context.MODE_PRIVATE).edit().putString("lang", lang).apply()
                setAppLocale(lang)
                recreate() // перезапуск Activity для применения локали
            }
            true
        }
        popup.show()
    }

    private fun setAppLocale(language: String) {
        val locale = Locale(language)
        Locale.setDefault(locale)
        val config = Configuration()
        config.setLocale(locale)
        resources.updateConfiguration(config, resources.displayMetrics)
    }

    private fun fetchWelcomeText() {
        val client = OkHttpClient()
        val request = Request.Builder()
            .url("http://46-30-41-84.sslip.io/api/welcome.php?lang=$lang")
            .build()

        client.newCall(request).enqueue(object : Callback {
            override fun onFailure(call: Call, e: IOException) {
                runOnUiThread {
                    welcomeTextView.text = getString(R.string.error_unable_to_fetch_data)
                }
            }

            override fun onResponse(call: Call, response: Response) {
                val responseData = response.body?.string()
                runOnUiThread {
                    // Меняем межстрочный интервал для русского языка
                    if (lang == "ru") {
                        welcomeTextView.setLineSpacing(0f, 1f) // минимальный межстрочный интервал
                    } else {
                        welcomeTextView.setLineSpacing(0f, 1.2f) // стандартный для английского
                    }

                    if (response.isSuccessful && responseData != null) {
                        try {
                            val json = JSONObject(responseData)
                            val sb = StringBuilder()

                            val welcomeHeading = json.optString("welcome_heading")
                            val welcomeSubheading = json.optString("welcome_subheading")
                            val advantagesHeading = json.optString("advantages_heading")
                            val advantages = listOf(
                                json.optString("advantages_text1"),
                                json.optString("advantages_text3"),
                                json.optString("advantages_text4"),
                                json.optString("advantages_text5")
                            ).filter { it.isNotBlank() }

                            if (welcomeHeading.isNotBlank()) sb.append(welcomeHeading).append("\n\n")
                            if (welcomeSubheading.isNotBlank()) sb.append(welcomeSubheading).append("\n\n")
                            if (advantagesHeading.isNotBlank()) sb.append(advantagesHeading).append("\n")
                            advantages.forEach { sb.append("• ").append(it).append("\n") }
                            if (advantages.isNotEmpty()) sb.append("\n")

                            if (json.optString("message_display") == "1") {
                                val message = json.optString("message")
                                if (message.isNotBlank()) {
                                    sb.append(message).append("\n")
                                }
                            }

                            welcomeTextView.text = sb.toString().trim()
                        } catch (e: Exception) {
                            welcomeTextView.text = getString(R.string.error_invalid_data_format)
                        }
                    } else {
                        welcomeTextView.text = getString(R.string.error_unable_to_fetch_data)
                    }
                }
            }
        })
    }
}
