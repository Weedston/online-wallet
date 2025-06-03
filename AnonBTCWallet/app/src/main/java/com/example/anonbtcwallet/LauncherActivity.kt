package com.example.anonbtcwallet

import android.content.Context
import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity

class LauncherActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val prefs = getSharedPreferences("auth", Context.MODE_PRIVATE)
        val pin = prefs.getString("pin", null)
        if (pin.isNullOrEmpty()) {
            startActivity(Intent(this, SplashActivity::class.java))
        } else {
            startActivity(Intent(this, PinEnterActivity::class.java))
        }
        finish()
    }
}

