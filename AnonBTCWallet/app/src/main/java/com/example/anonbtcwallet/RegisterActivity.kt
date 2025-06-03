package com.example.anonbtcwallet

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.jsoup.Jsoup
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody

class RegisterActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_register)

        val sidTextView = findViewById<TextView>(R.id.sidPhrase)
        val copyButton = findViewById<Button>(R.id.copySidButton)
        val toLoginButton = findViewById<Button>(R.id.toLoginButton)
        val walletTextView = findViewById<TextView>(R.id.walletTextView)
        val privkeyTextView = findViewById<TextView>(R.id.privkeyTextView)

        // Загружаем SID через API
        CoroutineScope(Dispatchers.Main).launch {
            val sidResult = fetchSidPhraseFromApi()
            if (sidResult.sidPhrase.isNotEmpty()) {
                sidTextView.text = sidResult.sidPhrase
                walletTextView.text = getString(R.string.wallet_label, sidResult.wallet)
                privkeyTextView.text = getString(R.string.privkey_label, sidResult.privkey)
                copyButton.text = getString(R.string.copy_sid)
                toLoginButton.text = getString(R.string.to_login)
                findViewById<TextView>(R.id.sidLabel).text = getString(R.string.sid_label)
                findViewById<TextView>(R.id.registerTitle).text = getString(R.string.register_link)
                findViewById<TextView>(R.id.sidInfo).text = getString(R.string.sid_info)
            } else {
                sidTextView.text = getString(R.string.network_error)
                walletTextView.text = ""
                privkeyTextView.text = ""
                copyButton.isEnabled = false
            }
        }
    }
}

data class SidResult(val sidPhrase: String, val wallet: String, val privkey: String)

suspend fun fetchSidPhraseFromApi(): SidResult = withContext(Dispatchers.IO) {
    val client = OkHttpClient()
    val url = "http://46-30-41-84.sslip.io/api/register.php"
    val request = Request.Builder().url(url).get().build()
    try {
        client.newCall(request).execute().use { response ->
            if (!response.isSuccessful) {
                // Показываем ошибку в логах и возвращаем пустой результат
                android.util.Log.e("RegisterActivity", "Ошибка загрузки: ${response.code} ${response.message}")
                return@withContext SidResult("", "", "")
            }
            val json = response.body?.string() ?: return@withContext SidResult("", "", "")
            try {
                val obj = org.json.JSONObject(json)
                val sid = obj.optString("sidPhrase", "")
                val wallet = obj.optString("wallet", "")
                val privkey = obj.optString("privkey", "")
                return@withContext SidResult(sid, wallet, privkey)
            } catch (e: Exception) {
                android.util.Log.e("RegisterActivity", "Ошибка разбора JSON: $json", e)
                return@withContext SidResult("", "", "")
            }
        }
    } catch (e: Exception) {
        // Показываем ошибку в логах и возвращаем пустой результат
        android.util.Log.e("RegisterActivity", "Ошибка загрузки: ", e)
        return@withContext SidResult("", "", "")
    }
}

