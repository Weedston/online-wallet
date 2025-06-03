package com.example.anonbtcwallet

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.view.MenuItem
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.GravityCompat
import com.google.android.material.navigation.NavigationView
import androidx.drawerlayout.widget.DrawerLayout
import android.widget.TextView
import android.widget.Toast
import androidx.fragment.app.Fragment

class MainActivity : AppCompatActivity(), NavigationView.OnNavigationItemSelectedListener {
    private lateinit var drawerLayout: DrawerLayout
    private lateinit var navigationView: NavigationView
    private lateinit var contentText: TextView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        drawerLayout = findViewById(R.id.drawer_layout)
        navigationView = findViewById(R.id.nav_view)
        contentText = findViewById(R.id.contentText)
        navigationView.setNavigationItemSelectedListener(this)
        // По умолчанию открываем Dashboard
        showDashboard()
    }

    override fun onNavigationItemSelected(item: MenuItem): Boolean {
        when (item.itemId) {
            R.id.nav_dashboard -> showDashboard()
            R.id.nav_transfer -> showTransfer()
            R.id.nav_support -> showSupport()
            R.id.nav_profile -> showProfile()
            R.id.nav_logout -> logout()
        }
        drawerLayout.closeDrawer(GravityCompat.START)
        return true
    }

    private fun showDashboard() {
        supportFragmentManager.beginTransaction()
            .replace(R.id.contentFrame, DashboardFragment())
            .commit()
    }

    private fun showTransfer() {
        contentText.text = getString(R.string.menu_transfer)
    }
    private fun showSupport() {
        contentText.text = getString(R.string.menu_support)
    }
    private fun showProfile() {
        contentText.text = getString(R.string.menu_profile)
    }
    private fun logout() {
        val prefs = getSharedPreferences("auth", Context.MODE_PRIVATE)
        prefs.edit().clear().apply()
        val intent = Intent(this, PasswordActivity::class.java)
        intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
        startActivity(intent)
        finish()
    }
}

