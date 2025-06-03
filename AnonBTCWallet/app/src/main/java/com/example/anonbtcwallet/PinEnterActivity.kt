package com.example.anonbtcwallet

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import androidx.appcompat.app.AppCompatActivity
import android.content.res.Configuration
import java.util.Locale

class PinEnterActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val prefs = getSharedPreferences("auth", Context.MODE_PRIVATE)
        val lang = prefs.getString("lang", "ru") ?: "ru"
        setAppLocale(lang)

        setContentView(R.layout.activity_pin_enter)

        val pinEditText = findViewById<EditText>(R.id.pinEditText)
        val enterPinButton = findViewById<Button>(R.id.enterPinButton)

        enterPinButton.setOnClickListener {
            val enteredPin = pinEditText.text.toString()
            val savedPin = prefs.getString("pin", null)
            if (enteredPin == savedPin) {
                startActivity(Intent(this, MainActivity::class.java))
                finish()
            } else {
                pinEditText.error = getString(R.string.wrong_pin)
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
