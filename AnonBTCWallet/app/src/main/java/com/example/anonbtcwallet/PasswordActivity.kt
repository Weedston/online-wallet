package com.example.anonbtcwallet

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import android.os.StrictMode
import okhttp3.*
import android.webkit.CookieManager
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.toRequestBody
import android.content.res.Configuration
import java.util.Locale

class PasswordActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val prefs = getSharedPreferences("auth", Context.MODE_PRIVATE)
        val lang = prefs.getString("lang", "ru") ?: "ru"
        setAppLocale(lang)

        // Восстановлена проверка sid и pin
        val sid = prefs.getString("sid", null)
        val pin = prefs.getString("pin", null)
        if (!sid.isNullOrEmpty() && !pin.isNullOrEmpty()) {
            // Если уже есть sid и pin, сразу переходим в MainActivity
            startActivity(Intent(this, MainActivity::class.java))
            finish()
            return
        }

        setContentView(R.layout.activity_password)

        val passwordEditText = findViewById<EditText>(R.id.passwordEditText)
        val loginButton = findViewById<Button>(R.id.loginButton)
        val registerLink = findViewById<TextView>(R.id.registerLink)

        registerLink.setOnClickListener {
            val intent = Intent(this, RegisterActivity::class.java)
            startActivity(intent)
        }

        loginButton.setOnClickListener {
            val password = passwordEditText.text.toString()
            if (password.isNotEmpty()) {
                loginButton.isEnabled = false
                passwordEditText.error = null
                val client = OkHttpClient()
                val json = "{" + "\"sid\":\"" + password + "\"}" // простая JSON-строка
                val body = json.toRequestBody("application/json; charset=utf-8".toMediaType())
                val request = Request.Builder()
                    .url("http://46-30-41-84.sslip.io/api/login.php")
                    .post(body)
                    .build()
                Thread {
                    try {
                        val response = client.newCall(request).execute()
                        val responseBody = response.body?.string() ?: ""
                        val obj = org.json.JSONObject(responseBody)
                        val status = obj.optString("status", "error")
                        runOnUiThread {
                            loginButton.isEnabled = true
                            if (status == "ok") {
                                prefs.edit()
                                    .putString("sid", password)
                                    .putString("token", obj.optString("token", ""))
                                    .putString("wallet", obj.optString("wallet", ""))
                                    .putInt("user_id", obj.optInt("user_id", 0))
                                    .apply()

                                // Логирование сохранённых данных
                                android.util.Log.d("PasswordActivity", "Saved user_id: ${obj.optInt("user_id", 0)}, token: ${obj.optString("token", "")}")

                                val intent = Intent(this, PinCreateActivity::class.java)
                                startActivity(intent)
                                finish()
                            } else {
                                val message = obj.optString("message", getString(R.string.wrong_sid))
                                passwordEditText.error = message
                            }
                        }
                    } catch (e: Exception) {
                        runOnUiThread {
                            loginButton.isEnabled = true
                            passwordEditText.error = getString(R.string.network_error)
                        }
                    }
                }.start()
            } else {
                passwordEditText.error = getString(R.string.sid_hint)
            }
        }
    }

    private fun setAppLocale(language: String) {
        val locale = Locale(language)
        Locale.setDefault(locale)
        val config = Configuration()
        config.setLocale(locale)
        resources.updateConfiguration(config, resources.displayMetrics)
    }
}
