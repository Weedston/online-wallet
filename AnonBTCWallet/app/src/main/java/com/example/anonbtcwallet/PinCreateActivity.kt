package com.example.anonbtcwallet

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import androidx.appcompat.app.AppCompatActivity
import android.content.res.Configuration
import java.util.Locale

class PinCreateActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val prefs = getSharedPreferences("auth", Context.MODE_PRIVATE)
        val lang = prefs.getString("lang", "ru") ?: "ru"
        setAppLocale(lang)

        setContentView(R.layout.activity_pin_create)

        val pinEditText = findViewById<EditText>(R.id.pinEditText)
        val savePinButton = findViewById<Button>(R.id.savePinButton)

        savePinButton.setOnClickListener {
            val pin = pinEditText.text.toString()
            if (pin.length == 4 && pin.all { it.isDigit() }) {
                prefs.edit().putString("pin", pin).apply()
                startActivity(Intent(this, MainActivity::class.java))
                finish()
            } else {
                pinEditText.error = getString(R.string.pin_create_hint)
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
