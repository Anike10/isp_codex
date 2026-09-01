# Netwala_MikroTik (RouterOS v7)
# Keep the router clock synchronized in Bangladesh time.
/system clock set time-zone-autodetect=no time-zone-name=Asia/Dhaka
/system ntp client set enabled=yes mode=unicast
/system ntp client servers remove [find]
/system ntp client servers add address=time.cloudflare.com iburst=yes
/system ntp client servers add address=time.google.com iburst=yes

# RouterOS v7 only originates an output.network prefix when an exact active
# route exists. More-specific connected/static routes will still win over this
# aggregate discard route.
:if ([:len [/ip route find where dst-address="162.4.6.0/24" and routing-table="main"]] = 0) do={
    /ip route add blackhole dst-address=162.4.6.0/24 routing-table=main comment="BGP origin anchor for AS154636"
}
